<?php

// All logical elements of a chessboard are in here. For visual elements see VisualBoard.
class ChessBoard2
{
	private $Board;     				// Array for storing all pieces on the board.
	private $PlayerTurn;                    // Player's turn. 0=white, 1=black
	private $fCastleWKingside, $fCastleWQueenside, $fCastleBKingside, $fCastleBQueenside;
	private $nEnPassanteSquare;            // Square behind the pawn that just made a 2 square move (for en passante).
	private $nHalfMoveCounter;             // How many half moves have occured since the last capture or pawn move.
	private $nFullMoveCounter;             // How many full moves have been made (if FEN loaded, then continues the move count)
	private $LastMoveType;     			// Category to which the last move belonged to.
	private $nStatus = CHESS_BOARD_STATUS::NORMAL; // Indicates the status of the board.
	private $nCalls = 0;
	private $fConvertToAN = true;         // Indicates wether or not to convert a move SAN and LAN. When verifying moves it is not really required.
	private $fUsedFEN = false;            // Indicates if a FEN was used to setup the board.

	// Bitboards variables.
	// Stores positions for each piece type for black and white.
	private $n64WPawns, $n64WRooks, $n64WKnights, $n64WBishops, $n64WQueens, $n64WKing;
	private $n64BPawns, $n64BRooks, $n64BKnights, $n64BBishops, $n64BQueens, $n64BKing;
	// Stores positions for all white pieces, all black pieces and white+black pieces combined.
	private $n64WAll, $n64BAll, $n64All;
	// These two bitboard arrays will store which tiles can be attacked from a given tile
	// and which tiles can attack a given tile. The tile is used as the index into the arrays.
	//UInt64[] m_rgAttacksFrom = new UInt64[64];  <-- not used
	//UInt64[] m_rgAttacksTo = new UInt64[64];  <-- not used
	// These bitboard arrays will store which tiles can be attacked for the king,
	// knight and pawn piece types. For pawns two arrays are needed since black and
	// white pawns move in opposite directions.
	private $KingAttacks, $KnightAttacks, $WPawnAttacks, $BPawnAttacks, $WPawnCaptures, $BPawnCaptures;
	// The en passant bitboard contains the square that can be taken en passant for the current turn.
	// The castle bitboards contain the tiles that a king can move to to castle for white and black.
	private $n64Enpassant, $n64WhiteCastle, $n64BlackCastle;
	// Will mask the bits that need to be free to castle.
	private $n64WhiteCastleQFree, $n64WhiteCastleKFree, $n64BlackCastleQFree, $n64BlackCastleKFree;
	// These bitboards will store the tiles that can be attacked for sliding pieces in each direction.
	private $Plus1, $Plus8, $Plus9, $Plus7, $Minus1, $Minus7, $Minus8, $Minus9;
	// More bitboards.
	private $Ranks;     // Will store 8 boards with a different rank's bits set to 1.
	private $Tiles;     // Used to mask a specific tile in a bitmap.

	private $szMoveMessage;
	private $MoveList;
	//List<ENUMS.ChessPieceType> m_rgTakenPieceTypes = new List<ENUMS.ChessPieceType>();

	private $ChessUtils;       // Contains various utility functions.
	private $floor;				// Lookup map to make tile indices (0 to 63) to ranks. Quicker than doing floor(tile/8).

	/// <summary>
	/// Sets up the chessboard class to its default state.
	/// </summary>
	public function ChessBoard2()
	{
		$this->floor = array(0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 6, 6, 6, 6, 7, 7, 7, 7, 7, 7, 7, 7);
		$this->ChessUtils = new ChessBoardUtilities();
		$this->SetupAttackBitboards();
		$this->ResetBoard();
	}

	/// <summary>
	/// Resets the board to the standard starting state. All moves made are lost.
	/// </summary>
	public function ResetBoard()
	{
		$Boards = new BitBoards();

		$this->fUsedFEN = false;

		// Create a standard board setup.
		$this->Board = array(
			array(PIECE_TYPE::ROOK, PIECE_TYPE::KNIGHT, PIECE_TYPE::BISHOP, PIECE_TYPE::QUEEN, PIECE_TYPE::KING, PIECE_TYPE::BISHOP, PIECE_TYPE::KNIGHT, PIECE_TYPE::ROOK),
			array(PIECE_TYPE::PAWN, PIECE_TYPE::PAWN, PIECE_TYPE::PAWN, PIECE_TYPE::PAWN, PIECE_TYPE::PAWN, PIECE_TYPE::PAWN, PIECE_TYPE::PAWN, PIECE_TYPE::PAWN),
			array(PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED),
			array(PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED),
			array(PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED),
			array(PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED, PIECE_TYPE::UNDEFINED),
			array(PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6, PIECE_TYPE::PAWN + 6),
			array(PIECE_TYPE::ROOK + 6, PIECE_TYPE::KNIGHT + 6, PIECE_TYPE::BISHOP + 6, PIECE_TYPE::QUEEN + 6, PIECE_TYPE::KING + 6, PIECE_TYPE::BISHOP + 6, PIECE_TYPE::KNIGHT + 6, PIECE_TYPE::ROOK + 6)
		);

		// Do the same for the pieces bitmaps.
		$this->n64WKing = new BitBoard('0x10');
		$this->n64WQueens = new BitBoard('0x8');
		$this->n64WBishops = new BitBoard('0x24');
		$this->n64WKnights = new BitBoard('0x42');
		$this->n64WRooks = new BitBoard('0x81');
		$this->n64WPawns = new BitBoard('0xFF00');
		$this->n64BKing = new BitBoard('0x1000000000000000');
		$this->n64BQueens = new BitBoard('0x800000000000000');
		$this->n64BBishops = new BitBoard('0x2400000000000000');
		$this->n64BKnights = new BitBoard('0x4200000000000000');
		$this->n64BRooks = new BitBoard('0x8100000000000000');
		$this->n64BPawns = new BitBoard('0xFF000000000000');
		$this->n64WAll = new BitBoard('0xFFFF');
		$this->n64BAll = new BitBoard('0xFFFF000000000000');
		$this->n64All = new BitBoard('0xFFFF00000000FFFF');
		// Other bitmaps to reset.
		$this->n64Enpassant = new BitBoard('0x0');
		$this->n64BlackCastle = new BitBoard('0x0');
		$this->n64WhiteCastle = new BitBoard('0x0');

		// Set board state variables
		$this->fCastleBKingside = $this->fCastleBQueenside = $this->fCastleWKingside = $this->fCastleWQueenside = true;
		$this->nStatus = CHESS_BOARD_STATUS::NORMAL;
		$this->LastMoveType = CHESS_BOARD_STATUS::NORMAL;
		$this->nFullMoveCounter = 1;
		$this->nHalfMoveCounter = 0;
		$this->PlayerTurn = 0;
		$this->nEnPassantSquare = -1;
		//m_rgTakenPieceTypes = new List<ENUMS.ChessPieceType>();

		// Create a movelist object. Need to pass the starting state of the game to it.
		$Boards->n64BBishops = $this->n64BBishops->duplicate();
		$Boards->n64BCastling = $this->n64BlackCastle->duplicate();
		$Boards->n64BKing = $this->n64BKing->duplicate();
		$Boards->n64BKnights = $this->n64BKnights->duplicate();
		$Boards->n64BPawns = $this->n64BPawns->duplicate();
		$Boards->n64BQueens = $this->n64BQueens->duplicate();
		$Boards->n64BRooks = $this->n64BRooks->duplicate();
		$Boards->n64EnPassant = $this->n64Enpassant->duplicate();
		$Boards->n64WBishops = $this->n64WBishops->duplicate();
		$Boards->n64WCastling = $this->n64WhiteCastle->duplicate();
		$Boards->n64WKing = $this->n64WKing->duplicate();
		$Boards->n64WKnights = $this->n64WKnights->duplicate();
		$Boards->n64WPawns = $this->n64WPawns->duplicate();
		$Boards->n64WQueens = $this->n64WQueens->duplicate();
		$Boards->n64WRooks = $this->n64WRooks->duplicate();
		$this->MoveList = new ChessMoveList(0, $this->fCastleWKingside,
									 $this->fCastleWQueenside, $this->fCastleBKingside, $this->fCastleBQueenside,
									 $this->nEnPassantSquare, $this->nHalfMoveCounter, $this->nStatus, $Boards); 

		//LogFile.Write("\nReset the board to its normal initial state!\n");
	}

	/// <summary>
	/// Populate the board pieces array and the bitboards based on the board setup field 
	/// values of a FEN string.
	/// </summary>
	/// <param name="szFEN">A string containing the board setup.</param>
	private function SetupBoardArrayFromFEN($FEN)
	{
		$Rows = array();      // Stores each row of the FEN
		$Rows = preg_split('/\//', $FEN);
		$col = 0;

		for($row = 0; $row < 8; $row++)
		{
			for($col = 0; $col < 8; $col++)
			{
				$this->Board[$row][$col] = PIECE_TYPE::UNDEFINED;
			}
		}
		
		// Process each rank. (8 is the first one and 1 the last)
		for ($row = 7; $row > -1; $row--)
		{
			$col = 0;
			// Process each character.
			for ($nCnt = 0; $nCnt < strlen($Rows[7 - $row]); $nCnt++)
			{
				switch ($Rows[7 - $row][$nCnt])
				{
					//black pieces
					case 'r':
						$this->Board[$row][$col] = PIECE_TYPE::ROOK + 6;
						break;
					case 'n':
						$this->Board[$row][$col] = PIECE_TYPE::KNIGHT + 6;
						break;
					case 'b':
						$this->Board[$row][$col] = PIECE_TYPE::BISHOP + 6;
						break;
					case 'q':
						$this->Board[$row][$col] = PIECE_TYPE::QUEEN + 6;
						break;
					case 'k':
						$this->Board[$row][$col] = PIECE_TYPE::KING + 6;
						break;
					case 'p':
						$this->Board[$row][$col] = PIECE_TYPE::PAWN + 6;
						break;
					//white pieces
					case 'R':
						$this->Board[$row][$col] = PIECE_TYPE::ROOK;
						break;
					case 'N':
						$this->Board[$row][$col] = PIECE_TYPE::KNIGHT;
						break;
					case 'B':
						$this->Board[$row][$col] = PIECE_TYPE::BISHOP;
						break;
					case 'Q':
						$this->Board[$row][$col] = PIECE_TYPE::QUEEN;
						break;
					case 'K':
						$this->Board[$row][$col] = PIECE_TYPE::KING;
						break;
					case 'P':
						$this->Board[$row][$col] = PIECE_TYPE::PAWN;
						break;
					//spaces
					case '1':
						$col = $col + 0;
						break;
					case '2':
						$col = $col + 1;
						break;
					case '3':
						$col = $col + 2;
						break;
					case '4':
						$col = $col + 3;
						break;
					case '5':
						$col = $col + 4;
						break;
					case '6':
						$col = $col + 5;
						break;
					case '7':
						$col = $col + 6;
						break;
					case '8':
						$col = $col + 7;
						break;
				}
				$col++;
			}
		}
		// Populate the bitboards based on the state of the board array.
		$this->SetupBitBoards();
	}

	/// <summary>
	/// Sets up the game with a given FEN string and validates the board to make
	/// sure all values provided with the FEN are legal. If not then the board
	/// is set to the standard starting position.
	/// </summary>
	/// <param name="szFEN">A FEN string to use to setup the board.</param>
	/// <returns>Returns true if the game was setup successfully from the FEN.</returns>
	public function SetupBoardWithFEN($FEN)
	{
		$nWKing; $nBKing;
		$fAttacked = false;
		$szIgnore = "";

		// Make sure the FEN structure is valid.
		if ($this->ChessUtils->ValidateFENStringStructureAndValues($FEN, $szIgnore))
		{
			// Set some class state variables.
			$this->fCastleBKingside = $this->fCastleBQueenside = $this->fCastleWKingside = $this->fCastleWQueenside = false;
			$this->nStatus = CHESS_BOARD_STATUS::NORMAL;
			$this->LastMoveType = MOVE_TYPE::NORMAL;

			// Split the fen string into its components.
			$Fields = preg_split('/\s/', $FEN);      // Stores each field (space delimited item).

			$this->SetupBoardArrayFromFEN($Fields[0]);

			// Check if castling is allowed.
			$this->fCastleWKingside = STRING_UTILS::in_string($Fields[2], "K");
			$this->fCastleWQueenside = STRING_UTILS::in_string($Fields[2], "Q");
			$this->fCastleBKingside = STRING_UTILS::in_string($Fields[2], "k");
			$this->fCastleBQueenside = STRING_UTILS::in_string($Fields[2], "q");

			// Get the en passant tile and the 50move and fullmove numbers
			$this->nEnPassantSquare = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger($Fields[3]);
			$this->nHalfMoveCounter = (int)$Fields[4];
			$this->nFullMoveCounter = 1;//Convert.ToInt32($Fields[5]);  ?? why 1?

			// Check both kings to make sure that only one can possibly be in check.
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nWKing = $tmp[0];
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nBKing = $tmp[0];
			$this->PlayerTurn = 1;
			if (!$this->FindAttacksToTile($this->floor[$nWKing], $nWKing % 8)->_AND($this->n64BAll)->is_zero())
				$fAttacked = true;
			$this->PlayerTurn = 0;
			if (!$this->FindAttacksToTile($this->floor[$nBKing], $nBKing % 8)->_AND($this->n64WAll)->is_zero())
			{
				if ($fAttacked)
				{
					$this->ResetBoard();
					return false;
				}
			}

			// Get whose turn it is.
			if ($Fields[1] == "w")
				$this->PlayerTurn = 0;
			else
				$this->PlayerTurn = 1;

			// If castling flags are set, make sure there is a rook for the corresponding flag
			// and that the king is in its original position.
			if ($this->fCastleWQueenside)
			{
				if ( BitBoard::_AND_($this->n64WKing, new BitBoard('10'))->is_zero() || BitBoard::_AND_($this->n64WRooks, new BitBoard('1'))->is_zero() )
				{
					$this->ResetBoard();
					return false;
				}
			}
			if ($this->fCastleWKingside)
			{
				if ( BitBoard::_AND_($this->n64WKing, new BitBoard('10'))->is_zero() || BitBoard::_AND_($this->n64WRooks, new BitBoard('80'))->is_zero() )
				{
					$this->ResetBoard();
					return false;
				}
			}
			if ($this->fCastleBQueenside)
			{
				if (BitBoard::_AND_($this->n64BKing, new BitBoard('1000000000000000'))->is_zero() || BitBoard::_AND_($this->n64BRooks, new BitBoard('100000000000000'))->is_zero() )
				{
					$this->ResetBoard();
					return false;
				}
			}
			if ($this->fCastleBKingside)
			{
				if (BitBoard::_AND_($this->n64BKing, new BitBoard('0x1000000000000000'))->is_zero() || BitBoard::_AND_($this->n64BRooks, new BitBoard('8000000000000000'))->is_zero() )
				{
					$this->ResetBoard();
					return false;
				}
			}
			// Update the tiles that can be castled to for both kings.
			$this->UpdateCastlingBitmaps();
			// Evaluate the state of the board.
			$this->ReEvaluateBoardState();
		}
		else
		{
			// Use default setup.
			//KMessageBox.Show("Errors in fen: " + szIgnore); 
			echo $szIgnore;
			$this->ResetBoard();
			return false;
		}

		// Create a movelist object. Need to pass the starting state of the game to it.
		$Boards = new BitBoards();
		$Boards->n64BBishops = $this->n64BBishops->duplicate();
		$Boards->n64BCastling = $this->n64BlackCastle->duplicate();
		$Boards->n64BKing = $this->n64BKing->duplicate();
		$Boards->n64BKnights = $this->n64BKnights->duplicate();
		$Boards->n64BPawns = $this->n64BPawns->duplicate();
		$Boards->n64BQueens = $this->n64BQueens->duplicate();
		$Boards->n64BRooks = $this->n64BRooks->duplicate();
		$Boards->n64EnPassant = $this->n64Enpassant->duplicate();
		$Boards->n64WBishops = $this->n64WBishops->duplicate();
		$Boards->n64WCastling = $this->n64WhiteCastle->duplicate();
		$Boards->n64WKing = $this->n64WKing->duplicate();
		$Boards->n64WKnights = $this->n64WKnights->duplicate();
		$Boards->n64WPawns = $this->n64WPawns->duplicate();
		$Boards->n64WQueens = $this->n64WQueens->duplicate();
		$Boards->n64WRooks = $this->n64WRooks->duplicate();
		$this->MoveList = new ChessMoveList($this->PlayerTurn, $this->fCastleWKingside,
									 $this->fCastleWQueenside, $this->fCastleBKingside, $this->fCastleBQueenside,
									 $this->nEnPassantSquare, $this->nHalfMoveCounter, $this->nStatus, $Boards);
		
		$this->fUsedFEN = true;

		return true;
	}


	/// <summary>
	/// Validates a FEN and returns  a list of errors if there were any. NOTE: The board will be reset
	/// after the validation of the FEN.  ******** Create a version of this function that saves the current bitboards and doesn't reset stuff!!!! *********
	/// </summary>
	/// <param name="FEN">A string containing the FEN to validate.</param>
	/// <param name="szErrors">A reference to a string that will store any errors that may be found.</param>
	/// <returns>Returns true if the FEN setup is ok.</returns>
	public function ValidateFEN($FEN, $szErrors)
	{
		$nWKing; $nBKing;
		$fWKingCheck = false; $fBKingCheck = false;
		$szErrors = "";

		// Make sure the FEN structure is valid.
		if ($this->ChessUtils->ValidateFENStringStructureAndValues($FEN, $szErrors))
		{
			// Set some class state variables.
			$this->fCastleBKingside = $this->fCastleBQueenside = $this->fCastleWKingside = $this->fCastleWQueenside = false;
			$this->nStatus = CHESS_BOARD_STATUS::NORMAL;
			$this->LastMoveType = MOVE_TYPE::NORMAL;

			// Split the fen string into its components.
			$Fields = preg_split(' ', $FEN);      // Stores each field (space delimited item).

			$this->SetupBoardArrayFromFEN($Fields[0]);

			// Check if castling is allowed.
			$this->fCastleWKingside = strstr($Fields[2], "K");
			$this->fCastleWQueenside = strstr($Fields[2], "Q");
			$this->fCastleBKingside = strstr($Fields[2], "k");
			$this->fCastleBQueenside = strstr($Fields[2], "q");

			// Get the en passant tile and the 50move and fullmove numbers
			$this->nEnPassantSquare = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger($Fields[3]);
			$this->nHalfMoveCounter = (int)$Fields[4];
			$this->nFullMoveCounter = 1;//Convert.ToInt32($Fields[5]);	?? why 1? 

			// Check both kings to make sure that only one can possibly be in check.
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nWKing = $tmp[0];
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nBKing = $tmp[0];
			$this->PlayerTurn = 1;
			if ( !$this->FindAttacksToTile($this->floor[$nWKing], $nWKing % 8)->_AND($this->n64BAll)->is_zero() )
				$fWKingCheck = true;
			$this->PlayerTurn = 0;
			if ( !$this->FindAttacksToTile($this->floor[$nBKing], $nBKing % 8)->_AND($this->n64WAll)->is_zero() )
				$fBKingCheck = true;
			if ($fWKingCheck && $fBKingCheck)
			{
				$this->ResetBoard();
				$szErrors = "Both Kings are in check!";
				return false;
			}

			// Get whose turn it is.
			if ($Fields[1] == "w")
				$this->PlayerTurn = 0;
			else
				$this->PlayerTurn = 1;

			// Make sure if a king is in check that it is its turn.
			if ($fWKingCheck && $this->PlayerTurn == 1)
				$szErrors = "White King in check, but it is black's turn.\n";
			else if ($fBKingCheck && $this->PlayerTurn == 0)
				$szErrors = "Black King in check, but it is white's turn.\n";

			// If castling flags are set, make sure there is a rook for the corresponding flag
			// and that the king is in its original position.
			if ($this->fCastleWQueenside)
			{
				if ( BitBoard::_AND_($this->n64WKing, new BitBoard('10'))->is_zero() || 
					 BitBoard::_AND_($this->n64WRooks, new BitBoard('1'))->is_zero() )
					$szErrors .= "White cannot castle queenside.\n";
			}
			if ($this->fCastleWKingside)
			{
				if ( BitBoard::_AND_($this->n64WKing, new BitBoard('10'))->is_zero() || 
					 BitBoard::_AND_($this->n64WRooks, new BitBoard('80'))->is_zero() )
					$szErrors .= "White cannot castle kingside.\n";
			}
			if ($this->fCastleBQueenside)
			{
				if ( BitBoard::_AND_($this->n64BKing, new BitBoard('1000000000000000'))->is_zero() || 
					 BitBoard::_AND_($this->n64BRooks, new BitBoard('100000000000000'))->is_zero() )
					$szErrors .= "Black cannot castle queenside.\n";
			}
			if ($this->fCastleBKingside)
			{
				if ( BitBoard::_AND_($this->n64BKing, new BitBoard('1000000000000000'))->is_zero() || 
					 BitBoard::_AND_($this->n64BRooks, new BitBoard('800000000000000'))->is_zero() )
					$szErrors .= "Black cannot castle kingside.\n";
			}

			// Make sure the en passant square is valid. Need to have a pawn one rank higher/lower.
			if ($this->nEnPassantSquare != -1)
			{
				if ($this->PlayerTurn == 0)
				{
					// Need to make sure there is a black pawn where we expect one.
					if ($this->nEnPassantSquare >= 40 && $this->nEnPassantSquare <= 47)
					{
						if ($this->Board[$this->nEnPassantSquare / 8 - 1][$this->nEnPassantSquare & 8] != PIECE_TYPE::PAWN + 6)
						{
							$szErrors .= "The en passant square is invalid.\n";
						}
					}
					else
						$szErrors .= "The en passant square is invalid.\n";
				}
				else
				{
					// Need to make sure there is a black pawn where we expect one.
					if ($this->nEnPassantSquare >= 16 && $this->nEnPassantSquare <= 23)
					{
						if ($this->Board[$this->nEnPassantSquare / 8 + 1][$this->nEnPassantSquare & 8] != PIECE_TYPE::PAWN)
						{
							$szErrors .= "The en passant square is invalid.\n";
						}
					}
					else
						$szErrors .= "The en passant square is invalid.\n";
				}
			}

			// Update the tiles that can be castled to for both kings.
			$this->UpdateCastlingBitmaps();
			
			// Evaluate the state of the board.
			$this->ReEvaluateBoardState();

			if ($this->nStatus == CHESS_BOARD_STATUS::STALEMATE)
				$szErrors .= "Game is in a stalemate.\n";
			else if ($this->nStatus == CHESS_BOARD_STATUS::MATE)
				$szErrors .= "Game is in a check mate state.\n";

		}

		// Use default setup.
		$this->ResetBoard();

		if ($szErrors == "")
			return true;
		else
			return false;
	}


	/// <summary>
	/// Sets up the pieces bitboards based on the the board pieces array.
	/// </summary>
	private function SetupBitBoards()
	{
		$this->n64WRooks->set_value('0');
		$this->n64WPawns->set_value('0');
		$this->n64WQueens->set_value('0');
		$this->n64WKnights->set_value('0');
		$this->n64WKing->set_value('0');
		$this->n64WBishops->set_value('0');
		$this->n64BRooks->set_value('0');
		$this->n64BPawns->set_value('0');
		$this->n64BQueens->set_value('0');
		$this->n64BKnights->set_value('0');
		$this->n64BKing->set_value('0');
		$this->n64BBishops->set_value('0');
		$this->n64WAll->set_value('0');
		$this->n64BAll->set_value('0');
		$this->n64All->set_value('0');
		
		$one = new BitBoard('1');
		
		// Store the position of all pieces according to type and player colour
		for ($row = 0; $row < 8; $row++)
		{
			for ($col = 0; $col < 8; $col++)
			{
				switch ($this->Board[$row][$col])
				{
					case PIECE_TYPE::PAWN:
						$this->n64WPawns->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::ROOK:
						$this->n64WRooks->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::KNIGHT:
						$this->n64WKnights->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::BISHOP:
						$this->n64WBishops->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::QUEEN:
						$this->n64WQueens->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::KING:
						$this->n64WKing->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::PAWN + 6:
						$this->n64BPawns->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::ROOK + 6:
						$this->n64BRooks->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::KNIGHT + 6:
						$this->n64BKnights->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::BISHOP + 6:
						$this->n64BBishops->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::QUEEN + 6:
						$this->n64BQueens->_OR($one->shift_l_($col + $row * 8));
						break;
					case PIECE_TYPE::KING + 6:
						$this->n64BKing->_OR($one->shift_l_($col + $row * 8));
						break;
				} //end case select
			} //end inner loop
		}//end outer loop

		// Now combine the bitboards for white pieces and for black pieces to get the position of all pieces.
		$this->n64WAll->_OR($this->n64WBishops)->_OR($this->n64WKing)->_OR($this->n64WKnights)->_OR($this->n64WPawns)->_OR($this->n64WQueens)->_OR($this->n64WRooks);
		$this->n64BAll->_OR($this->n64BBishops)->_OR($this->n64BKing)->_OR($this->n64BKnights)->_OR($this->n64BPawns)->_OR($this->n64BQueens)->_OR($this->n64BRooks);
		$this->n64All->_OR($this->n64BAll)->_OR($this->n64WAll);
	}

	/// <summary>
	/// Generates all moves possible for a given starting tile.
	/// </summary>
	/// <param name="nFile">The file of the tile to generate moves for.</param>
	/// <param name="nRank">The rank of the tile to generate moves for.</param>
	/// <param name="MoveList">A reference to a list which will be used for returning the tiles that can be moved to.</param>
	/// <returns>Return Values: -1 Invalid tile | 0 No moves possible | >0 Number of possible moves with MoveList containing the possible moves.</returns>
	public function GenerateMovesForPiece($nRank, $nFile, &$MoveList)
	{
		$nKingTile; $nAttackFile; $nAttackRank; $nStartTile;
		$possibleMoves = array();

		// If start rank/file are not valid then return.
		if ($nFile < 0 || $nFile > 7 || $nRank < 0 || $nRank > 7) return -1;

		// Get a bitmap of all tiles that can be attacked.
		$attackBoard = $this->FindAttacksFromTile($nRank, $nFile);
		$nStartTile = $nRank * 8 + $nFile;
		// Filter out tiles that contain this side's own pieces and add any castling 
		// moves if king is selected. Then for each tile that can be moved to,
		// temporarily move the piece and see if the king is not attacked. If yes,
		// then add the tile to the move list.
		if ($this->PlayerTurn == 0)
		{
			$attackBoard->_XOR(BitBoard::_AND_($attackBoard, $this->n64WAll));
			if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KING)
				$attackBoard->_OR($this->n64WhiteCastle);
			$possibleMoves = $this->ChessUtils->GetTilesFromBitmap($attackBoard);
			//if ($this->n64WKing == 0) { rgMoveList.Clear(); return -1; }
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nKingTile = $tmp[0];
			foreach ($possibleMoves as $tile)
			{
				$nAttackFile = $tile % 8;
				$nAttackRank = $this->floor[$tile];
				$this->Board_RemovePiece($nAttackFile, $nAttackRank);
				$this->Board_MovePiece($nFile, $nRank, $nAttackFile, $nAttackRank);
				$this->PlayerTurn = 1;
				// Check if the king gets moved or not.
				if ($nStartTile != $nKingTile)
				{
					// Check if this is an en passant move
					if ($tile != $this->nEnPassantSquare)
					{
						$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
						if ($tmp->_AND($this->n64BAll)->is_zero())
							$MoveList[] = $tile;
					}
					else
					{
						// Check if a pawn moved to the en passant tile or if it was another type of piece.
						if ($this->Board[$nRank][$nFile] == PIECE_TYPE::PAWN)
						{
							// Took a piece via en passant. Need to remove the pawn before checking if
							// the king is still in check.
							$this->Board_RemovePiece($tile % 8, $this->floor[$tile] - 1);
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64BAll)->is_zero())
								$MoveList[] = $tile;
							$this->Board_AddPiece($tile % 8, $this->floor[$tile] - 1, PIECE_TYPE::PAWN + 6);
						}
						else
						{
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64BAll)->is_zero)
								$MoveList[] = $tile;
						}
					}
				}
				else
				{
					$tmp = $this->FindAttacksToTile($nAttackRank, $nAttackFile);
					if ($tmp->_AND($this->n64BAll)->is_zero())
						$MoveList[] = $tile;
				}
				$this->PlayerTurn = 0;
				$this->Board_MovePiece_($nAttackFile, $nAttackRank, $nFile, $nRank, $this->Board[$nRank][$nFile]);
				$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
			}
		}
		else
		{
			$attackBoard->_XOR(BitBoard::_AND_($attackBoard, $this->n64BAll));
			if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KING + 6)
				$attackBoard->_OR($this->n64BlackCastle);
			$PossibleMoves = $this->ChessUtils->GetTilesFromBitmap($attackBoard);
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nKingTile = $tmp[0];
			foreach ($possibleMoves as $tile)
			{
				$nAttackFile = $tile % 8;
				$nAttackRank = $this->floor[$tile];
				$this->Board_RemovePiece($nAttackFile, $nAttackRank);
				$this->Board_MovePiece($nFile, $nRank, $nAttackFile, $nAttackRank);
				$this->PlayerTurn = 0;
				if ($nStartTile != $nKingTile)
				{
					if ($tile != $this->nEnPassantSquare)
					{
						$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
						if ($tmp->_AND($this->n64WAll) == 0)
							$MoveList[] = $tile;
					}
					else
					{
						if ($this->Board[$nRank][$nFile] == PIECE_TYPE::PAWN + 6)
						{
							$this->Board_RemovePiece($tile % 8, $this->floor[$tile] + 1);
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64WAll)->is_zero())
								$MoveList[] = $tile;
							$this->Board_AddPiece($tile % 8, $this->floor[$tile] + 1, PIECE_TYPE::PAWN);
						}
						else
						{
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64WAll)->is_zero())
								$MoveList[] = $tile;
						}
					}
				}
				else
				{
					$tmp = $this->FindAttacksToTile($nAttackRank, $nAttackFile);
					if ($tmp->_AND($this->n64WAll)->is_zero())
						$MoveList[] = $tile;
				}
				$this->PlayerTurn = 1;
				$this->Board_MovePiece_($nAttackFile, $nAttackRank, $nFile, $nRank, $this->Board[$nRank][$nFile]);
				$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
			}
		}
		return count($MoveList);
	}

	/// <summary>
	/// Returns 0 if piece at location is white, 1 if black, and -1 if no piece or invalid tile.
	/// </summary>
	/// <param name="nRank">The rank the piece is on.</param>
	/// <param name="nFile">The file the piece is on.</param>
	/// <returns>Return the player whose piece this is. If the rank/file are not right, -1 is returned.</returns>
	public function GetPlayerOfPieceAtLocation($nRank, $nFile)
	{
		if ($nRank < 0 || $nRank > 7 || $nFile < 0 || $nFile > 7)
			return -1;
		if ((int)$this->Board[$nRank][$nFile] < 7 && (int)$this->Board[$nRank][$nFile] > 0) return 0;
		if ((int)$this->Board[$nRank][$nFile] < 13 && (int)$this->Board[$nRank][$nFile] > 6) return 1;
		return -1;
	}

	// Returns an array storing the position of all pieces as they are on the board.
	public function GetBoardSetup()
	{
		return $this->Board;
	}
	public function PlayerTurn()
	{
		return $this->PlayerTurn;
	}
	public function GetFullMoves()
	{
		return $this->nFullMoveCounter;
	}
	public function GetHalfMoveClock()
	{
		return $this->nHalfMoveCounter;
	}
	public function GetMoveType()
	{
		return $this->LastMoveType;
	}

	public function IsPromotionPossible($nStartRow, $nStartCol, $nDestRow)
	{
		// Check if there is a pawn at destination and that its value is rank 0 or 7
		if (($this->Board[$nStartRow][$nStartCol] == PIECE_TYPE::PAWN ||
			$this->Board[$nStartRow][$nStartCol] == PIECE_TYPE::PAWN + 6) &&
			($nDestRow == 7 || $nDestRow == 0))
			return true;
		return false;
	}

	/// <summary>
	/// Moves a piece from the given start tile to the end tile and promotes it if needed.
	/// </summary>
	/// <param name="nFromTile">Start tile as integer (index in the board array).</param>
	/// <param name="nToTile">End tile as integer (index in the board array).</param>
	/// <param name="promotionType">Reference to a var containing the type to promote piece to. This value is set to UNDEFINED if a promotion couldn't be made.</param>
	/// <param name="moveType">Reference to a var that will contain the type of move just made.</param>
	/// <param name="fRecordMove">Record the move in the move list.</param>
	public function MakeMove($nFromTile, $nToTile, &$promotionType, &$moveType, $fRecordMove, $fEvaluateBoard = true)
	{
		$nRow1 = $this->floor[$nFromTile];
		$nCol1 = $nFromTile % 8;
		$nRow2 = $this->floor[$nToTile];
		$nCol2 = $nToTile % 8;
		$this->MakeMove_($nRow1, $nCol1, $nRow2, $nCol2, $promotionType, $moveType, $fRecordMove, $fEvaluateBoard);
	}
	
	/// <summary>
	/// Moves a piece from the given start row & column to the given end row & column, and promotes if if needed.
	/// </summary>
	/// <param name="nRow1">Start Rank.</param>
	/// <param name="nCol1">Start File.</param>
	/// <param name="nRow2">End Rank.</param>
	/// <param name="nCol2">End File.</param>
	/// <param name="promotionType">Reference to a var containing the type to promote piece to. This value is set to UNDEFINED if a promotion couldn't be made.</param>
	/// <param name="moveType">Reference to a var that will contain the type of move just made.</param>
	/// <param name="fRecordMove">Record the move in the move list.</param> 
	/// <param name="fEvaluateBoard">Indicates if the board state should be re-evaluated after the move. If applying moves setting this to false will speed things up.</param> 
	public function MakeMove_($nRow1, $nCol1, $nRow2, $nCol2, &$promotionType, &$moveType, $fRecordMove, $fEvaluateBoard = true)
	{
		$szSAN = "UNDEF"; $szLong = "UNDEF";
		$Boards = new BitBoards();
		$pieceType = $this->Board[$nRow1][$nCol1];
		$moveType = $this->DetermineMoveType($nRow1, $nCol1, $nRow2, $nCol2, $promotionType);
		// Convert move if needed to algebraic notations.
		if ($this->fConvertToAN)
		{
			// Convert move to long algebraic notation and PNG
			$szLong = $this->ChessUtils->ConvertMoveToLongNotation($nRow1 * 8 + $nCol1, $nRow2 * 8 + $nCol2, $this->Board[$nRow1][$nCol1], $promotionType);
			// Convert move to SAN notation.
			if ($pieceType > 6) $pieceType -= 6;
			$szSAN = $this->ConvertMoveToSAN($nCol1, $nRow1, $nCol2, $nRow2, $pieceType, $moveType, $promotionType);
		}
		
		// If a piece was captured, record what type it was. This info can be used to display list of taken pieces 
		// for each player.
		$takenPieceType = PIECE_TYPE::UNDEFINED;
		if($moveType == MOVE_TYPE::CAPTURED)
		{
			$takenPieceType = $this->Board[$nRow2][$nCol2];
			if($takenPieceType > 6) $takenPieceType -= 6;
		}
		else if($moveType == MOVE_TYPE::ENPASSANT)
		{
			$takenPieceType = PIECE_TYPE::PAWN;
		}
		
		// Apply the move which sets some values for castling and the en passant tile.
		$this->ApplyMove($nRow1, $nCol1, $nRow2, $nCol2, $promotionType, $moveType);

		// Re-evaluate the state of the board. This can be skipped if you know the move being added legal.
		if($fEvaluateBoard) $this->ReEvaluateBoardState();

		if ($this->fConvertToAN)
		{
			// If the board status is check or mate then add the required characters
			if ($this->nStatus == CHESS_BOARD_STATUS::CHECK)
				$szSAN .= "+";
			else if ($this->nStatus == CHESS_BOARD_STATUS::MATE)
				$szSAN .= "#";
		}

		if ($fRecordMove)
		{
			// Records the move and other things needed to set the game state properly when stepping through moves.
			$Boards->n64BBishops = $this->n64BBishops->duplicate();
			$Boards->n64BCastling = $this->n64BlackCastle->duplicate();
			$Boards->n64BKing = $this->n64BKing->duplicate();
			$Boards->n64BKnights = $this->n64BKnights->duplicate();
			$Boards->n64BPawns = $this->n64BPawns->duplicate();
			$Boards->n64BQueens = $this->n64BQueens->duplicate();
			$Boards->n64BRooks = $this->n64BRooks->duplicate();
			$Boards->n64EnPassant = $this->n64Enpassant->duplicate();
			$Boards->n64WBishops = $this->n64WBishops->duplicate();
			$Boards->n64WCastling = $this->n64WhiteCastle->duplicate();
			$Boards->n64WKing = $this->n64WKing->duplicate();
			$Boards->n64WKnights = $this->n64WKnights->duplicate();
			$Boards->n64WPawns = $this->n64WPawns->duplicate();
			$Boards->n64WQueens = $this->n64WQueens->duplicate();
			$Boards->n64WRooks = $this->n64WRooks->duplicate();
			$this->MoveList->RecordMove($nRow1 * 8 + $nCol1, $nRow2 * 8 + $nCol2, $moveType, $takenPieceType, $promotionType,
				$this->fCastleWKingside, $this->fCastleWQueenside, $this->fCastleBKingside, $this->fCastleBQueenside,
				$this->nEnPassantSquare, $this->nHalfMoveCounter, $szSAN, $szLong, $this->nStatus, "", $Boards);
		}

		$this->szMoveMessage = $szSAN;
	}
	
	/// <summary>
	/// Determines the type of move made from the start and end tile positions passed. Also
	/// makes sure that a promotion can be performed only when a pawn has reached the end rank.
	/// </summary>
	/// <param name="nRow1">Start Rank</param>
	/// <param name="nCol1">Start File</param>
	/// <param name="nRow2">End Rank</param>
	/// <param name="nCol2">End File</param>
	/// <param name="promotionType">Reference to a variable containing the piece type requested for a promotion. This variable is set to UNDEFINED if a promotion cannot be performed.</param>
	/// <returns>Returns the move type.</returns>
	public function DetermineMoveType($nRow1, $nCol1, $nRow2, $nCol2, &$promotionType)
	{
		// Assume a normal move by default
		$lastMoveType = MOVE_TYPE::NORMAL;

		// If the passed 'promoteType' variable is not 'undefined' then check if a promotion can be
		// performed.
		if ($promotionType != PIECE_TYPE::UNDEFINED)
		{
			// If the start tile is not occupied by a pawn, or the end rank is not 0 or 7 depending
			// on the player turn, then a promotion cannot be performed.
			if ($this->Board[$nRow1][$nCol1] == PIECE_TYPE::PAWN + 6 * $this->PlayerTurn)
			{
				if ($this->PlayerTurn == 0 && $nRow2 != 7)
					$promotionType = PIECE_TYPE::UNDEFINED;
				else if ($this->PlayerTurn == 1 && $nRow2 != 0)
					$promotionType = PIECE_TYPE::UNDEFINED;
			}
			else
			{
				$promotionType = PIECE_TYPE::UNDEFINED;
			}
		}

		// If dest was the same as the en passant square and the moved piece was a pawn,
		// then an en passante moved occured.
		if ($this->nEnPassantSquare == ($nRow2 * 8 + $nCol2))
		{
			if ($this->Board[$nRow1][$nCol1] == PIECE_TYPE::PAWN ||
				$this->Board[$nRow1][$nCol1] == PIECE_TYPE::PAWN + 6)
			{
				$this->nEnPassantSquare = -1;
				return MOVE_TYPE::ENPASSANT;
			}
			$this->nEnPassantSquare = -1;
		}

		// If dest is occupied then the piece was captured
		if ($this->Board[$nRow2][$nCol2] > 0)
		{
			return MOVE_TYPE::CAPTURED;
		}

		// If start tile had a king on it and the destination tile is more than 1 tile
		// away then check if castling was kingside or queenside.
		if ($this->Board[$nRow1][$nCol1] == PIECE_TYPE::KING ||
			$this->Board[$nRow1][$nCol1] == PIECE_TYPE::KING + 6)
		{
			if ($this->PlayerTurn == 0 && $nRow1 == $nRow2 && abs($nCol1 - $nCol2) > 1)  //white
			{
				if ($nCol1 < $nCol2)
					return MOVE_TYPE::CASTLEK;
				else
					return MOVE_TYPE::CASTLEQ;
			}
			if ($this->PlayerTurn == 1 && $nRow1 == $nRow2 && abs($nCol1 - $nCol2) > 1)  //black
			{
				if ($nCol1 < $nCol2)
					return MOVE_TYPE::CASTLEK;
				else
					return MOVE_TYPE::CASTLEQ;
			}
		}

		return $lastMoveType;
	}
	
	/// <summary>
	/// Applies a move if given the start and end tile coordinates, plus the type being promoted to and the type of move to make.
	/// </summary>
	/// <param name="nRank1">Start rank.</param>
	/// <param name="nFile1">Start file.</param>
	/// <param name="nRank2">End rank.</param>
	/// <param name="nFile2">End file.</param>
	/// <param name="promotionType">The piece type to promote a piece to.</param>
	/// <param name="moveType">The move type.</param>
	public function ApplyMove($nRank1, $nFile1, $nRank2, $nFile2, $promotionType, $moveType)
	{
		$this->nEnPassantSquare = -1;
		// Check if a pawn was moved and reset the half move counter if so. Else increment it.
		if ($this->Board[$nRank1][$nFile1] == PIECE_TYPE::PAWN ||
			$this->Board[$nRank1][$nFile1] == PIECE_TYPE::PAWN + 6)
			$this->nHalfMoveCounter = 0;
		else
			$this->nHalfMoveCounter++;

		// Make the move by moving necessary piece references.
		switch ($moveType)
		{
			case MOVE_TYPE::NORMAL:
				// Check if a pawn was moved two tiles forward. If so set the enpassante tile
				// to the value of the tile behind the pawn.
				if ($this->Board[$nRank1][$nFile1] == PIECE_TYPE::PAWN && $nRank2 - $nRank1 == 2)   //white pawn
					$this->nEnPassantSquare = ($nRank2 - 1) * 8 + $nFile2;
				else if ($this->Board[$nRank1][$nFile1] == PIECE_TYPE::PAWN + 6 && $nRank1 - $nRank2 == 2)   //black pawn
					$this->nEnPassantSquare = ($nRank2 + 1) * 8 + $nFile2;
				else  // Reset en passante square since this piece wasn't a pawn or a pawn that didn't move 2 tiles.
					$this->nEnPassantSquare = -1;

				// Check if a king was moved or rook was moved/taken, then disallow castling as needed.
				if ($this->fCastleWKingside || $this->fCastleWQueenside)
				{
					if ($nRank1 == 0 && $nFile1 == 4)
						$this->fCastleWKingside = $this->fCastleWQueenside = false;           // King moved
					if ($nRank1 == 0 && $nFile1 == 0) $this->fCastleWQueenside = false;    // rook in file A moved so no queenside castling is possible anymore
					if ($nRank1 == 0 && $nFile1 == 7) $this->fCastleWKingside = false;     // rook in file H moved so no kingside castling is possible anymore
					if ($nRank2 == 0 && $nFile2 == 0) $this->fCastleWQueenside = false;    // rook in file A taken so no queenside castling is possible anymore
					if ($nRank2 == 0 && $nFile2 == 7) $this->fCastleWKingside = false;     // rook in file H taken so no kingside castling is possible anymore
				}
				if ($this->fCastleBKingside || $this->fCastleBQueenside)
				{
					if ($nRank1 == 7 && $nFile1 == 4)
						$this->fCastleBKingside = $this->fCastleBQueenside = false;           // King moved
					if ($nRank1 == 7 && $nFile1 == 0) $this->fCastleBQueenside = false;    // rook in file A moved
					if ($nRank1 == 7 && $nFile1 == 7) $this->fCastleBKingside = false;     // rook in file H moved
					if ($nRank2 == 7 && $nFile2 == 0) $this->fCastleBQueenside = false;    // rook in file A taken
					if ($nRank2 == 7 && $nFile2 == 7) $this->fCastleBKingside = false;     // rook in file H taken
				}

				// Move the piece reference and update bitboards.
				$this->Board_MovePiece($nFile1, $nRank1, $nFile2, $nRank2);
				$this->Board[$nRank2][$nFile2] = $this->Board[$nRank1][$nFile1];
				$this->Board[$nRank1][$nFile1] = PIECE_TYPE::UNDEFINED;
				break;
			case MOVE_TYPE::CAPTURED:
				// Add the type of the taken piece to the list
				//m_rgTakenPieceTypes.Add($this->Board[nRank2, nFile2]);
				$this->Board_RemovePiece($nFile2, $nRank2);
				$this->Board_MovePiece($nFile1, $nRank1, $nFile2, $nRank2);
				$this->Board[$nRank2][$nFile2] = $this->Board[$nRank1][$nFile1];
				$this->Board[$nRank1][$nFile1] = PIECE_TYPE::UNDEFINED;
				// Reset 50 move
				$this->nHalfMoveCounter = 0;
				// If king was used to capture then disable castling for that side.
				if ($this->fCastleWKingside || $this->fCastleWQueenside)
				{
					if ($nRank1 == 0 && $nFile1 == 4) $this->fCastleWKingside = $this->fCastleWQueenside = false;           // King moved
				}
				if ($this->fCastleBKingside || $this->fCastleBQueenside)
				{
					if ($nRank1 == 7 && $nFile1 == 4) $this->fCastleBKingside = $this->fCastleBQueenside = false;           // King moved
				}
				break;
			case MOVE_TYPE::ENPASSANT:
				// Move pawn that attacked.
				$this->Board_MovePiece($nFile1, $nRank1, $nFile2, $nRank2);
				$this->Board[$nRank2][$nFile2] = $this->Board[$nRank1][$nFile1];
				$this->Board[$nRank1][$nFile1] = PIECE_TYPE::UNDEFINED;
				// Depending on turn, remove the piece located one rank up or down from nRank2
				if ($this->PlayerTurn == 1)
				{
					//m_rgTakenPieceTypes.Add(rgBoard[nRank2 + 1, nFile2]);
					$this->Board_RemovePiece($nFile2, $nRank2 + 1);
					$this->Board[$nRank2 + 1][$nFile2] = PIECE_TYPE::UNDEFINED;
					//$this->nEnPassantSquare = (nRank2 + 1) * 8 + nFile2;
				}
				else
				{
					//m_rgTakenPieceTypes.Add(rgBoard[nRank2 - 1, nFile2]);
					$this->Board_RemovePiece($nFile2, $nRank2 - 1);
					$this->Board[$nRank2 - 1][$nFile2] = PIECE_TYPE::UNDEFINED;
					//$this->nEnPassantSquare = (nRank2 - 1) * 8 + nFile2;
				}
				$this->nEnPassantSquare = -1;
				break;
			case MOVE_TYPE::CASTLEK:
				// Move King and Rook to new positions
				$this->Board_MovePiece($nFile1, $nRank1, $nFile2, $nRank2);
				$this->Board[$nRank2][$nFile2] = $this->Board[$nRank1][$nFile1];
				$this->Board[$nRank1][$nFile1] = PIECE_TYPE::UNDEFINED; ;
				if ($this->PlayerTurn == 0)
				{
					$this->Board_MovePiece(7, 0, 5, 0);
					$this->Board[0][5] = $this->Board[0][7];
					$this->Board[0][7] = PIECE_TYPE::UNDEFINED;
					$this->fCastleWKingside = $this->fCastleWQueenside = false;
				}
				else
				{
					$this->Board_MovePiece(7, 7, 5, 7);
					$this->Board[7][5] = $this->Board[7][7];
					$this->Board[7][7] = PIECE_TYPE::UNDEFINED;
					$this->fCastleBKingside = $this->fCastleBQueenside = false;
				}
				break;
			case MOVE_TYPE::CASTLEQ:
				// Move King and Rook to new positions
				$this->Board_MovePiece($nFile1, $nRank1, $nFile2, $nRank2);
				$this->Board[$nRank2][$nFile2] = $this->Board[$nRank1][$nFile1];
				$this->Board[$nRank1][$nFile1] = PIECE_TYPE::UNDEFINED; ;
				if ($this->PlayerTurn == 0)
				{
					$this->Board_MovePiece(0, 0, 3, 0);
					$this->Board[0][3] = $this->Board[0][0];
					$this->Board[0][0] = PIECE_TYPE::UNDEFINED;
					$this->fCastleWKingside = $this->fCastleWQueenside = false;
				}
				else
				{
					$this->Board_MovePiece(0, 7, 3, 7);
					$this->Board[7][3] = $this->Board[7][0];
					$this->Board[7][0] = PIECE_TYPE::UNDEFINED;
					$this->fCastleBKingside = $this->fCastleBQueenside = false;
				}
				break;
		}
		// If promoting then change piece type
		if ($promotionType != PIECE_TYPE::UNDEFINED)
		{
			if ($this->PlayerTurn == 1) $promotionType = $promotionType + 6;
			$this->Board_RemovePiece($nFile2, $nRank2);
			$this->Board_AddPiece($nFile2, $nRank2, $promotionType);
			$this->Board[$nRank2][$nFile2] = $promotionType;
		}

		// Update bitmaps.
		$this->n64All = BitBoard::_OR_($this->n64BAll, $this->n64WAll);

		if ($this->nEnPassantSquare > -1)
			$this->n64Enpassant = $this->Tiles[$this->nEnPassantSquare];
		else
			$this->n64Enpassant = new BitBoard();

		$this->PlayerTurn = ($this->PlayerTurn == 1) ? 0 : 1;
		if ($this->PlayerTurn == 0) $this->nFullMoveCounter++;
		
		// Update the tiles that can be castled to for both kings.
		$this->UpdateCastlingBitmaps();

	}

	/// <summary>
	/// Tests if it is possible to move from a given start tile to a given end tile.
	/// </summary>
	/// <param name="nRank">The rank of the tile moving from.</param>
	/// <param name="nFile">The file of the tile moving from.</param>
	/// <param name="nRank2">The rank of the tile moving to.</param>
	/// <param name="nFile2">The file of the tile moving to.</param>
	/// <returns>Returns true if it is possible to make this move.</returns>
	public function IsMoveValid($nRank, $nFile, $nRank2, $nFile2)
	{
		//$nKingTile, $nAttackFile, $nAttackRank, $nStartTile;
		$possibleMoves = array();
		$fMoveOK = false;

		// If start/end rank/file are not valid then return.
		if ($nFile < 0 || $nFile > 7 || $nRank < 0 || $nRank > 7) return false;
		if ($nFile2 < 0 || $nFile2 > 7 || $nRank2 < 0 || $nRank2 > 7) return false;

		// Get a bitmap of all tiles that can be attacked and then mask it with the end tile.
		$attackBoard = $this->FindAttacksFromTile($nRank, $nFile);
		if ($this->PlayerTurn == 0)
		{
			$attackBoard->_XOR(BitBoard::_AND_($attackBoard, $this->n64WAll));
			if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KING)
				$attackBoard->_OR($this->n64WhiteCastle);
		}
		else
		{
			$attackBoard->_XOR(BitBoard::_AND_($attackBoard, $this->n64BAll));
			if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KING + 6)
				$attackBoard->_OR($this->n64BlackCastle);
		}
		$attackBoard->_AND($this->Tiles[$nRank2 * 8 + $nFile2]);
		// If empty then cannot move to the end tile.
		if ($attackBoard->is_zero())
			return false;
		$nStartTile = $nRank * 8 + $nFile;
		// Filter out tiles that contain this side's own pieces and add any castling 
		// moves if king is selected. Then for each tile that can be moved to,
		// temporarily move the piece and see if the king is not attacked. If yes,
		// then add the tile to the move list.
		if ($this->PlayerTurn == 0)
		{
			$possibleMoves = $this->ChessUtils->GetTilesFromBitmap($attackBoard);
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nKingTile = $tmp[0];
			foreach ($possibleMoves as $tile)
			{
				$nAttackFile = $tile % 8;
				$nAttackRank = $this->floor[$tile];
				$this->Board_RemovePiece($nAttackFile, $nAttackRank);
				$this->Board_MovePiece($nFile, $nRank, $nAttackFile, $nAttackRank);
				$this->PlayerTurn = 1;
				// Check if the king gets moved or not.
				if ($nStartTile != $nKingTile)
				{
					// Check if this is an en passant move
					if ($tile != $this->nEnPassantSquare)
					{
						$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
						if ($tmp->_AND($this->n64BAll)->is_zero())
							$fMoveOK = true;
					}
					else
					{
						// Check if a pawn moved to the en passant tile or if it was another type of piece.
						if ($this->Board[$nRank][$nFile] == PIECE_TYPE::PAWN)
						{
							// Took a piece via en passant. Need to remove the pawn before checking if
							// the king is still in check.
							$this->Board_RemovePiece($tile % 8, $this->floor[$tile] - 1);
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64BAll)->is_zero())
								$fMoveOK = true;
							$this->Board_AddPiece($tile % 8, $this->floor[$tile] - 1, PIECE_TYPE::PAWN + 6);
						}
						else
						{
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64BAll)->is_zero())
								$fMoveOK = true;
						}
					}
				}
				else
				{
					$tmp = $this->FindAttacksToTile($nAttackRank, $nAttackFile);
					if ($tmp->_AND($this->n64BAll)->is_zero())
						$fMoveOK = true;
				}
				$this->PlayerTurn = 0;
				$this->Board_MovePiece_($nAttackFile, $nAttackRank, $nFile, $nRank, $this->Board[$nRank][$nFile]);
				$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
			}
		}
		else
		{
			$possibleMoves = $this->ChessUtils->GetTilesFromBitmap($attackBoard);
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nKingTile = $tmp[0];
			foreach ($possibleMoves as $tile)
			{
				$nAttackFile = $tile % 8;
				$nAttackRank = $this->floor[$tile];
				$this->Board_RemovePiece($nAttackFile, $nAttackRank);
				$this->Board_MovePiece($nFile, $nRank, $nAttackFile, $nAttackRank);
				$this->PlayerTurn = 0;
				if ($nStartTile != $nKingTile)
				{
					if ($tile != $this->nEnPassantSquare)
					{
						$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
						if ($tmp->_AND($this->n64WAll)->is_zero())
							$fMoveOK = true;
					}
					else
					{
						if ($this->Board[$nRank][$nFile] == PIECE_TYPE::PAWN + 6)
						{
							$this->Board_RemovePiece($tile % 8, $this->floor[$tile] + 1);
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64WAll)->is_zero())
								$fMoveOK = true;
							$this->Board_AddPiece($tile % 8, $this->floor[$tile] + 1, PIECE_TYPE::PAWN);
						}
						else
						{
							$tmp = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
							if ($tmp->_AND($this->n64WAll)->is_zero())
								$fMoveOK = true;
						}
					}
				}
				else
				{
					$tmp = $this->FindAttacksToTile($nAttackRank, $nAttackFile);
					if ($tmp->_AND($this->n64WAll)->is_zero())
						$fMoveOK = true;
				}
				$this->PlayerTurn = 1;
				$this->Board_MovePiece_($nAttackFile, $nAttackRank, $nFile, $nRank, $this->Board[$nRank][$nFile]);
				$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
			}
		}
		return $fMoveOK;
	}

	/// <summary>
	/// Re-evaluates the state of the board. The board state can be either:
	/// normal, check, mate, 50 move or stalemate.
	/// </summary>
	public function ReEvaluateBoardState()
	{
		$this->nCalls++;
		// Test if the side is in check or mate. If neither then test for stalemate.
		$this->TestForCheckAndMate($this->nStatus);
		// // Update the tiles that can be castled to for both kings.
		// $this->UpdateCastlingBitmaps();

		if ($this->nStatus != CHESS_BOARD_STATUS::NORMAL) return;

		// Test if the current state is a stalemate.
		if ($this->IsSideInStalemate())
		{
			$this->nStatus = CHESS_BOARD_STATUS::STALEMATE;
			return;
		}

		// Test if the 50move rule is applicable.
		if ($this->nHalfMoveCounter >= 50) $this->nStatus = CHESS_BOARD_STATUS::FIFTY;
	}

	/// <summary>
	/// Removes the piece at the specified tile position by updating the corresponding bitboard(s).
	/// </summary>
	/// <param name="nCol">The file of the piece to remove</param>
	/// <param name="nRow">The rank of the piece to remove</param>
	private function Board_RemovePiece($nCol, $nRow)
	{
		// Get the piece type on this tile.
		$this->Board_RemovePiece_($nCol, $nRow, $this->Board[$nRow][$nCol]);
	}

	private function Board_RemovePiece_($nCol, $nRow, $pieceType)
	{
		// A piece gets removed from the bitboard for its specific type as well as the overall white/black bitboard.
		$pos = new BitBoard('0x1');
		$pos->shift_l($nCol + $nRow * 8);
		switch ($pieceType)
		{
			case PIECE_TYPE::PAWN:
				$this->n64WPawns->_XOR($pos);
				$this->n64WAll->_XOR($pos);
				break;
			case PIECE_TYPE::ROOK:
				$this->n64WRooks->_XOR($pos);
				$this->n64WAll->_XOR($pos);
				break;
			case PIECE_TYPE::KNIGHT:
				$this->n64WKnights->_XOR($pos);
				$this->n64WAll->_XOR($pos);
				break;
			case PIECE_TYPE::BISHOP:
				$this->n64WBishops->_XOR($pos);
				$this->n64WAll->_XOR($pos);
				break;
			case PIECE_TYPE::QUEEN:
				$this->n64WQueens->_XOR($pos);
				$this->n64WAll->_XOR($pos);
				break;
			case PIECE_TYPE::KING:
				$this->n64WKing->_XOR($pos);
				$this->n64WAll->_XOR($pos);
				break;
			case PIECE_TYPE::PAWN + 6:
				$this->n64BPawns->_XOR($pos);
				$this->n64BAll->_XOR($pos);
				break;
			case PIECE_TYPE::ROOK + 6:
				$this->n64BRooks->_XOR($pos);
				$this->n64BAll->_XOR($pos);
				break;
			case PIECE_TYPE::KNIGHT + 6:
				$this->n64BKnights->_XOR($pos);
				$this->n64BAll->_XOR($pos);
				break;
			case PIECE_TYPE::BISHOP + 6:
				$this->n64BBishops->_XOR($pos);
				$this->n64BAll->_XOR($pos);
				break;
			case PIECE_TYPE::QUEEN + 6:
				$this->n64BQueens->_XOR($pos);
				$this->n64BAll->_XOR($pos);
				break;
			case PIECE_TYPE::KING + 6:
				$this->n64BKing->_XOR($pos);
				$this->n64BAll->_XOR($pos);
				break;
		}
		$this->n64All = BitBoard::_OR_($this->n64BAll, $this->n64WAll);
	}

	private function Board_AddPiece($nCol, $nRow, $pieceType)
	{
		// A piece gets added to the bitboard for its specific type as well as to the overall white/black bitboard.
		$pos = new BitBoard('0x1');
		$pos->shift_l($nCol + $nRow * 8);
		switch ($pieceType)
		{
			case PIECE_TYPE::PAWN:
				$this->n64WPawns->_OR($pos);
				$this->n64WAll->_OR($pos);
				break;
			case PIECE_TYPE::ROOK:
				$this->n64WRooks->_OR($pos);
				$this->n64WAll->_OR($pos);
				break;
			case PIECE_TYPE::KNIGHT:
				$this->n64WKnights->_OR($pos);
				$this->n64WAll->_OR($pos);
				break;
			case PIECE_TYPE::BISHOP:
				$this->n64WBishops->_OR($pos);
				$this->n64WAll->_OR($pos);
				break;
			case PIECE_TYPE::QUEEN:
				$this->n64WQueens->_OR($pos);
				$this->n64WAll->_OR($pos);
				break;
			case PIECE_TYPE::KING:
				$this->n64WKing->_OR($pos);
				$this->n64WAll->_OR($pos);
				break;
			case PIECE_TYPE::PAWN + 6:
				$this->n64BPawns->_OR($pos);
				$this->n64BAll->_OR($pos);
				break;
			case PIECE_TYPE::ROOK + 6:
				$this->n64BRooks->_OR($pos);
				$this->n64BAll->_OR($pos);
				break;
			case PIECE_TYPE::KNIGHT + 6:
				$this->n64BKnights->_OR($pos);
				$this->n64BAll->_OR($pos);
				break;
			case PIECE_TYPE::BISHOP + 6:
				$this->n64BBishops->_OR($pos);
				$this->n64BAll->_OR($pos);
				break;
			case PIECE_TYPE::QUEEN + 6:
				$this->n64BQueens->_OR($pos);
				$this->n64BAll->_OR($pos);
				break;
			case PIECE_TYPE::KING + 6:
				$this->n64BKing->_OR($pos);
				$this->n64BAll->_OR($pos);
				break;
		}
		$this->n64All = BitBoard::_OR_($this->n64BAll, $this->n64WAll);
	}

	/// <summary>
	/// Moves a piece by updating the bitboards, from one tile to another.
	/// </summary>
	/// <param name="nCol1">The source tile file.</param>
	/// <param name="nRow1">The source tile rank.</param>
	/// <param name="nCol2">The destination tile file.</param>
	/// <param name="nRow2">The destination tile rank.</param>
	private function Board_MovePiece($nCol1, $nRow1, $nCol2, $nRow2)
	{
		$this->Board_AddPiece($nCol2, $nRow2, $this->Board[$nRow1][$nCol1]);
		$this->Board_RemovePiece($nCol1, $nRow1);
	}

	/// <summary>
	/// Moves a piece of a certain type from one square to another.
	/// </summary>
	/// <param name="nFile1">The file of the tile to move the piece from.</param>
	/// <param name="nRank1">The rank of the tile to move the piece from.</param>
	/// <param name="nFile2">The file of the tile to move the piece to.</param>
	/// <param name="nRank2">The rank of the tile to move the piece to.</param>
	/// <param name="pieceType">The type of the piece to move.</param>
	private function Board_MovePiece_($nFile1, $nRank1, $nFile2, $nRank2, $pieceType)
	{
		$this->Board_AddPiece($nFile2, $nRank2, $pieceType);
		$this->Board_RemovePiece_($nFile1, $nRank1, $pieceType);
	}
	
	/// <summary>
	/// Returns the last move made in SAN.
	/// </summary>
	/// <returns></returns>
	public function GetLastMoveMade()
	{
		$szReturn = $this->szMoveMessage;
		$this->szMoveMessage = "";
		return $szReturn;
	}
	
	/// <summary>
	/// Returns the last move made as `from` and `to` coords.
	/// </summary>
	public function GetLastMoveAsToFromCoords()
	{
		$cnt = $this->MoveList->MoveCount();
		if($cnt > 0)
		{
			$move = $this->MoveList->GetMove($cnt);
			return array('from' => $move->nStartTile, 'to' => $move->nEndTile);
		}
		return array('from' => '', 'to' => '');
	}

	/// <summary>
	/// Converts a move received in SAN/LAN notation to start/end tile coordinates and makes the move if it is valid.
	/// </summary>
	/// <param name="szMsg">The move tp convert.</param>
	/// <param name="nStartTile">Reference to an integer that will store the start tile for the move.</param>
	/// <param name="nEndTile">Reference to an integer that will store the end tile for the move.</param>
	/// <param name="moveType">Reference to a variable that will store what type this move was.</param>
	/// <param name="promotionType">Reference to a variable that will store what type a pawn for promoted to.</param>
	/// <param name="fRecordMove">Tells the chessboard if it should record the move in its internal move list.</param>
	/// <returns>Returns true if the move could be converted and was legal.</returns>
	public function SetMoveReceived($szMsg, &$nStartTile, &$nEndTile, &$moveType, &$promotionType, $fRecordMove)
	{
		// Convert string to start and end tiles and make the move.
		if ($this->ConvertSANtoCoordinates($szMsg, $nStartTile, $nEndTile, $promotionType))
		{
			if ($this->IsMoveValid($this->floor[$nStartTile], $nStartTile % 8, $this->floor[$nEndTile], $nEndTile % 8))
			{
				$this->MakeMove($nStartTile, $nEndTile, $promotionType, $moveType, $fRecordMove);
				return true;
			}
			//else
			//	LogFile.Write("Move '" + szMsg + "' is not a valid move.");
			//LogFile.FlushToFile();
		}
		//else 
		//LogFile.Write("Couldn't determine move coordinates for '" + szMsg + "'");
		// if ($nStartTile == -1)
			// LogFile.Write("Could not determine the start tile.");
		// else if (nEndTile == -1)
			// LogFile.Write("Could not determine the end tile.");
		// LogFile.FlushToFile();
		return false;
	}

	/// <summary>
	/// Checks if the passed move is valid. This function is to be used when validating games where no movelist needs to be generated.
	/// </summary>
	/// <param name="szMove">The move to validate in algebraic notation.</param>
	/// <returns>Returns true if the move is valid.</returns>
	public function IsMoveValid2($szMove)
	{
		$nStartTile = 0; $nEndTile = 0;
		$promoType = PIECE_TYPE::UNDEFINED;
		$moveType = MOVE_TYPE::NORMAL;

		// Temporarily do not convert this move to algebraic notation.
		$this->fConvertToAN = false;
		// Convert string to start and end tiles and make the move.
		if ($this->ConvertSANtoCoordinates($szMove, $nStartTile, $nEndTile, $promoType))
		{
			if ($this->IsMoveValid($this->floor[$nStartTile], $nStartTile % 8, $this->floor[$nEndTile], $nEndTile % 8))
			{
				$this->MakeMove($nStartTile, $nEndTile, $promoType, $moveType, false);
				$this->fConvertToAN = true;
				return true;
			}
		}
		$this->fConvertToAN = true;
		return false;
	}

	public function GetCallsCount()
	{
		return $this->nCalls;
	}



	/// <summary>
	/// Converts a SAN (Standard Algebraic Notation) move to a start and end tile and returns true
	/// if all went well.
	/// </summary>
	/// <param name="szMove">The move string in SAN.</param>
	/// <param name="nStartTile">Variable to receive the start tile.</param>
	/// <param name="nEndTile">Variable to receive the end tile.</param>
	/// <returns>Returns true if string could be converted to a move.</returns>
	private function ConvertSANtoCoordinates($szMove, &$nStartTile, &$nEndTile, &$promotionType)
	{
		$nTmp;

		if (strlen($szMove) < 2) return false;
		//promotionType = PIECE_TYPE::UNDEFINED;
		// If there is a p in the text then remove it since we don't need to be told the move was en passant
		$nTmp = strpos($szMove, 'ep');
		if ($nTmp !== FALSE)
		{
			$szMove = substr($szMove, 0, $nTmp) . substr($szMove, $nTmp, strlen($szMove) - $nTmp - 2);
		}

		// Check if the move was check or checkmate and remove the last character
		if (STRING_UTILS::last_char($szMove) == "+")
		{
			//$szMove.Replace("+", "");
			$szMove = substr($szMove, 0, strlen($szMove) - 1);
		}
		$nTmp = strpos($szMove, '#');
		if ($nTmp !== FALSE)
		{
			// Extract the move made which caused the checkmate. Note that crafty sends 0-1 {black mates} after the #.
			//$szMove = $szMove.Substring(0, $szMove.Length - 1);
			$szMove = substr($szMove, 0, $nTmp);
		}
		// Determine the type of the move (castle-, check+, mate#, promotion=)
		if ($szMove == "O-O")    // Castle kingside O-O.
		{
			if ($this->PlayerTurn == 0)
			{
				$nStartTile = 4;
				$nEndTile = 6;
			}
			else
			{
				$nStartTile = 60;
				$nEndTile = 62;
			}
			return true;
		}
		else if ($szMove == "O-O-O")    // Castle queenside O-O-O.
		{
			if ($this->PlayerTurn == 0)
			{
				$nStartTile = 4;
				$nEndTile = 2;
			}
			else
			{
				$nStartTile = 60;
				$nEndTile = 58;
			}
			return true;
		}
		else if (strpos($szMove, '=') !== FALSE)   // Promotion
		{
			// Get type to promote to
			if (STRING_UTILS::last_char($szMove) == "N") $promotionType = PIECE_TYPE::KNIGHT;
			else if (STRING_UTILS::last_char($szMove) == "B") $promotionType = PIECE_TYPE::BISHOP;
			else if (STRING_UTILS::last_char($szMove) == "R") $promotionType = PIECE_TYPE::ROOK;
			else if (STRING_UTILS::last_char($szMove) == "Q") $promotionType = PIECE_TYPE::QUEEN;
			// Get tiles
			$szMove = substr($szMove, 0, strlen($szMove) - 2);
			// Check if there is an x to indicate a piece was taken. 
			if ($szMove.Contains("x"))
			{
				$nEndTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger($szMove.Substring(2));
				if ($this->PlayerTurn == 0)
					$nStartTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger($szMove.Substring(0, 1) + "7");
				else
					$nStartTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger($szMove.Substring(0, 1) + "2");
			}
			else    // Pawn moved just forward.
			{
				$nEndTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger($szMove);
				if ($this->PlayerTurn == 0) $nStartTile = $nEndTile - 8;
				else $nStartTile = $nEndTile + 8;
			}
			if ($nStartTile == -1 || $nEndTile == -1) 
				return false;
			return true;
		}
		// Check for promotions of type a1Q, hxe1Q, e8N... The last character is a piece type.
		else if (($szMove[strlen($szMove) - 1] == 'Q' || $szMove[strlen($szMove) - 1] == 'B' ||
				  $szMove[strlen($szMove) - 1] == 'N' || $szMove[strlen($szMove) - 1] == 'R'))
		{
			// Get type to promote to
			if ($szMove[strlen($szMove) - 1] == 'N') $promotionType = PIECE_TYPE::KNIGHT;
			else if ($szMove[strlen($szMove) - 1] == 'B') $promotionType = PIECE_TYPE::BISHOP;
			else if ($szMove[strlen($szMove) - 1] == 'R') $promotionType = PIECE_TYPE::ROOK;
			else if ($szMove[strlen($szMove) - 1] == 'Q') $promotionType = PIECE_TYPE::QUEEN;
			// Remove the last char and then continue on finding the start and end tiles for this move.
			$szMove = substr($szMove, 0, strlen($szMove) - 1);
			// Make sure the move is valid for further processing.
			if (strlen($szMove) < 2) return false;
		}

		// Last two chars indicate the end tile. Remove them from the string.
		$nEndTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger(substr(strlen($szMove) - 2));
		if ($nEndTile == -1) return false;
		$szMove = substr($szMove, 0, strlen($szMove) - 2);

		// Check if a piece was taken and remove the x character if so.
		if ($szMove[strlen($szMove) - 1] == 'x')
			$szMove = substr($szMove, 0, strlen($szMove) - 1);
		// Check the remaining length.
		if (strlen($szMove) == 0) // Indicates pawn moved
		{
			if ($this->PlayerTurn == 0)
			{
				//if (rgBoard[nEndTile / 8 - 1, nEndTile % 8] > 0) nStartTile = nEndTile - 8;
				//else nStartTile = nEndTile - 16;
				//return true;
				// If the end tile is not rank 4 that means a pawn couldn't have moved two tiles
				// up the rank.
				if($nEndTile / 8 != 3)
				{
					// Check for a single move forward.
					if($this->Board[$this->floor[$nEndTile] - 1][$nEndTile % 8] == PIECE_TYPE::PAWN) 
						$nStartTile = $nEndTile - 8;
					// If not then check if the destination tile is the same as the enpassant tile
					else if ($this->nEnPassantSquare == nEndTile)
					{
						// Check if there is a white pawn to the left/right one rank down from the end tile
						if ($nEndTile % 8 == 0)
						{
							if ($this->Board[$this->floor[$nEndTile] - 1][$nEndTile % 8 + 1] == PIECE_TYPE::PAWN)
								$nStartTile = $nEndTile - 7;
							else
								return false;
						}
						else if ($nEndTile % 8 > 0 && $nEndTile % 8 < 7)
						{
							if ($this->Board[$this->floor[$nEndTile] - 1][$nEndTile % 8 - 1] == PIECE_TYPE::PAWN)
								$nStartTile = $nEndTile - 9;
							else if ($this->Board[$this->floor[$nEndTile] - 1][$nEndTile % 8 + 1] == PIECE_TYPE::PAWN)
								$nStartTile = $nEndTile - 7;
							else
								return false;
						}
						else
						{
							if ($this->Board[$this->floor[$nEndTile] - 1][$nEndTile % 8 - 1] == PIECE_TYPE::PAWN)
								$nStartTile = $nEndTile - 9;
							else
								return false;
						}
					}
				}
				else
				{
					// Check for a single move forward.
					if ($this->Board[$this->floor[$nEndTile] - 1][$nEndTile % 8] == PIECE_TYPE::PAWN)
						$nStartTile = $nEndTile - 8;
					else if ($this->Board[$this->floor[$nEndTile] - 2][$nEndTile % 8] == PIECE_TYPE::PAWN)
						$nStartTile = $nEndTile - 16;
					else
					{
						// Check if there is a black pawn to the left/right one rank up from the end tile
						if ($nEndTile % 8 == 0)
						{
							if ($this->Board[$this->floor[$nEndTile] + 1][$nEndTile % 8 + 1] == PIECE_TYPE::PAWN + 6)
								$nStartTile = $nEndTile + 9;
							else
								return false;
						}
						else if ($nEndTile % 8 > 0 && $nEndTile % 8 < 7)
						{
							if ($this->Board[$this->floor[$nEndTile] + 1][$nEndTile % 8 - 1] == PIECE_TYPE::PAWN + 6)
								$nStartTile = $nEndTile + 7;
							else if ($this->Board[$this->floor[$nEndTile] + 1][$nEndTile % 8 + 1] == PIECE_TYPE::PAWN + 6)
								$nStartTile = $nEndTile + 9;
							else
								return false;
						}
						else
						{
							if ($this->Board[$this->floor[$nEndTile] + 1][$nEndTile % 8 - 1] == PIECE_TYPE::PAWN + 6)
								$nStartTile = $nEndTile + 7;
							else
								return false;
						}
					}
				}
			}
			else
			{
				//if ($this->Board[nEndTile / 8 + 1, nEndTile % 8] > 0) nStartTile = nEndTile + 8;
				//else nStartTile = nEndTile + 16;
				//return true;
				// If the end tile is not rank 5 that means a pawn couldn't have moved two tiles
				// down the rank.
				if ($this->floor[$nEndTile] != 4)
				{
					if($this->Board[$this->floor[$nEndTile] + 1][$nEndTile % 8] == PIECE_TYPE::PAWN + 6)
						$nStartTile = $nEndTile + 8;
					// If not then check if the destination tile is the same as the enpassant tile.
					else if ($this->nEnPassantSquare == $nEndTile)
					{
						$nStartTile = -1;
						return false;
					}
				}
				else
				{
					if ($this->Board[$this->floor[$nEndTile] + 1][$nEndTile % 8] == PIECE_TYPE::PAWN + 6)
						$nStartTile = $nEndTile + 8;
					else if ($this->Board[$this->floor[$nEndTile] + 2][$nEndTile % 8] == PIECE_TYPE::PAWN + 6)
						$nStartTile = $nEndTile + 16;
					else
					{
						$nStartTile = -1;
						return false;
					}
				}
			}
			return true;
		}
		if (strlen($szMove) == 1) // Indicates piece type or file for pawns
		{
			if($szMove == 'a' || $szMove == 'b' || $szMove == 'c' || $szMove == 'd' || $szMove == 'e' || $szMove == 'f' || $szMove == 'g' || $szMove == 'h') 
			{
				if ($this->PlayerTurn == 0) $nTmp = $nEndTile / 8;
				else $nTmp = $this->floor[$nEndTile] + 2;
				$nStartTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger(substr($szMove, 0, 1) . $nTmp);
			}
			else    // Find a piece of the given type that could attack the end tile.
			{
				if ($szMove == 'K') $nStartTile = $this->ObtainAttackingTileForPieceType(PIECE_TYPE::KING, $nEndTile);
				else if ($szMove == 'Q') $nStartTile = $this->ObtainAttackingTileForPieceType(PIECE_TYPE::QUEEN, $nEndTile);
				else if ($szMove == 'R') $nStartTile = $this->ObtainAttackingTileForPieceType(PIECE_TYPE::ROOK, $nEndTile);
				else if ($szMove == 'B') $nStartTile = $this->ObtainAttackingTileForPieceType(PIECE_TYPE::BISHOP, $nEndTile);
				else if ($szMove == 'N') $nStartTile = $this->ObtainAttackingTileForPieceType(PIECE_TYPE::KNIGHT, $nEndTile);
			}
			if ($nStartTile == -1)
				return false;
			return true;
		}
		if (strlen($szMove) == 2) // 2nd char indicates start file or rank
		{
			// Find the piece type for the given file or rank
			if ($szMove[0] == 'K') $nStartTile = $this->FindPieceLocatedInFileOrRank(PIECE_TYPE::KING, $szMove[1]);
			else if ($szMove[0] == 'Q') $nStartTile = $this->FindPieceLocatedInFileOrRank(PIECE_TYPE::QUEEN, $szMove[1]);
			else if ($szMove[0] == 'R') $nStartTile = $this->FindPieceLocatedInFileOrRank(PIECE_TYPE::ROOK, $szMove[1]);
			else if ($szMove[0] == 'B') $nStartTile = $this->FindPieceLocatedInFileOrRank(PIECE_TYPE::BISHOP, $szMove[1]);
			else if ($szMove[0] == 'N') $nStartTile = $this->FindPieceLocatedInFileOrRank(PIECE_TYPE::KNIGHT, $szMove[1]);
			else
			{
			}
			if ($nStartTile == -1)
				return false;
			return true;
		}
		if (strlen($szMove) == 3) // 1st char is type, and other two are coordinate position
		{
			$nStartTile = $this->ChessUtils->ConvertAlgebraicNotationTileToInteger(substr($szMove, 1));
			if ($nStartTile == -1)
				return false;
			return true;
		}

		return false;
	}

	/// <summary>
	/// Returns the starting tile for a piece of a given type which can attack the specified tile. 
	/// </summary>
	/// <param name="piece">The piece type (KING, QUEEN, ROOK, BISHOP, KNIGHT)</param>
	/// <param name="nTile">The tile to test for if it is attackable.</param>
	/// <returns>Returns the start tile for the piece found to be able to attack the given tile. If no piece was found then -1 is returned.</returns>
	private function ObtainAttackingTileForPieceType($piece, $nTile)
	{
		$nNum = 0;
		$Tiles = array();
		if ($this->PlayerTurn == 1) $nNum = 6;
		for ($nRow = 0; $nRow < 8; $nRow++)
		{
			for ($nCol = 0; $nCol < 8; $nCol++)
			{
				if ($this->Board[$nRow][$nCol] == $piece + $nNum)
				{
					$this->GenerateMovesForPiece($nRow, $nCol, $Tiles);
					foreach ($Tiles as $tile)
					{
						if ($tile == $nTile) return $nRow * 8 + $nCol;
					}
				}
			}
		}

		return -1;
	}
	
	/// <summary>
	/// Returns the starting tile for a piece of a specified type (for the current player) that is on a given row or rank.
	/// </summary>
	/// <param name="piece">The piece type (KING, QUEEN, ROOK, BISHOP, KNIGHT)</param>
	/// <param name="chRankOrFile">Specifies as a character a rank ('1'-'8') or file('a'-'h') in which to search for the piece.</param>
	/// <returns>Returns the starting tile if a piece was found. If no piece was found -1 is returned.</returns>
	private function FindPieceLocatedInFileOrRank($piece, $chRankOrFile)
	{
		$nRank = -1; $nFile = -1;
		// Check if a rank was specified.
		if ($chRankOrFile == '1' || $chRankOrFile == '2' || $chRankOrFile == '3' ||
			$chRankOrFile == '4' || $chRankOrFile == '5' || $chRankOrFile == '6' ||
			$chRankOrFile == '7' || $chRankOrFile == '8')
		{
			if ($chRankOrFile == '1') $nRank = 0;
			if ($chRankOrFile == '2') $nRank = 1;
			if ($chRankOrFile == '3') $nRank = 2;
			if ($chRankOrFile == '4') $nRank = 3;
			if ($chRankOrFile == '5') $nRank = 4;
			if ($chRankOrFile == '6') $nRank = 5;
			if ($chRankOrFile == '7') $nRank = 6;
			if ($chRankOrFile == '8') $nRank = 7;

			// Search the given rank for a piece of the specified type which is the same
			// colour as the current player whose turn it is.
			for ($nFile = 0; $nFile < 8; $nFile++)
			{
				if ($this->Board[$nRank][$nFile] == $piece + $this->PlayerTurn * 6)
					return $nRank * 8 + $nFile;
			}
		}
		// File specified.
		else
		{
			if ($chRankOrFile == 'a') $nFile = 0;
			if ($chRankOrFile == 'b') $nFile = 1;
			if ($chRankOrFile == 'c') $nFile = 2;
			if ($chRankOrFile == 'd') $nFile = 3;
			if ($chRankOrFile == 'e') $nFile = 4;
			if ($chRankOrFile == 'f') $nFile = 5;
			if ($chRankOrFile == 'g') $nFile = 6;
			if ($chRankOrFile == 'h') $nFile = 7;
			for ($nRank = 0; $nRank < 8; $nRank++)
			{
				if ($this->Board[$nRank][$nFile] == $piece + $this->PlayerTurn * 6)
					return $nRank * 8 + $nFile;
			}
		}

		return -1;
	}

	/// <summary>
	/// Returns an enumerated integer representing the state of the board at this move.
	/// </summary>
	/// <returns></returns>
	public function GetGameBoardStatus()
	{
		return $this->nStatus;
	}

	/// <summary>
	/// Undoes the previous move in the move list if possible.
	/// </summary>
	/// <param name="moveInfo">Reference to a moveInfo variable to store the move info in.</param>
	public function UndoMove(MoveInfo &$moveInfo)
	{
		//int nPrvPlyMove = $this->nFullMoveCounter * 2 + $this->PlayerTurn - 2;
		$moveInfo = $this->MoveList->GetMove($this->MoveList->MoveCount());
		$prvMove = new MoveInfo();
		if ($this->MoveList->RemoveLastMove($prvMove) == false)
			return false;
		//
		//if ($this->nFullMoveCounter == 0) $this->nFullMoveCounter = 1;
		if ($this->RestoreBoardStateFromRecordedMove($SprvMove, $this->nFullMoveCounter) == false)
		{
			return false;
		}
		//if ($this->PlayerTurn == 1) $this->nFullMoveCounter--;
		return true;
	}

	/// <summary>
	/// Redoes the next move if possible for the selected variation.
	/// </summary>
	/// <param name="moveInfo">Reference to the moveinfo variable to store the move values in.</param>
	/// <param name="nVariation">Sets the variation to use if possible. 0 is the default variation</param>
	/// <returns>Returns true if the move was redone.</returns>
	public function RedoMove(MoveInfo &$moveInfo, $nVariation)
	{
		//if (($this->nFullMoveCounter - m_InitState.nFullMoveCount) * 2 + nPlayerTurn
		//    + m_InitState.nStartSide < MoveList.MoveCount)
		//{
		//    if (MoveList.GetMove(($this->nFullMoveCounter - m_InitState.nFullMoveCount) * 2 +
		//        nPlayerTurn + m_InitState.nStartSide + 1, ref moveInfo) == false) return false;
		//    ApplyMove(moveInfo.nStartTile / 8, moveInfo.nStartTile % 8, moveInfo.nEndTile / 8,
		//        moveInfo.nEndTile % 8, moveInfo.promotedToType, moveInfo.moveType);
		//    $this->nStatus = moveInfo.boardStatus;
		//    return true;
		//}
		return false;
	}

	/// <summary>
	/// Provides a list of moves going from the old move to the new move.
	/// </summary>
	/// <param name="nNewMove">The ply move number for the new move.</param>
	/// <param name="nOldMove">The ply move number for the previous move.</param>
	/// <param name="MoveList">A list of moves from the old move to the new one.</param>
	/// <returns>Returns true if the new move is valid.</returns>
	public function GetMoveRange($nNewMove, $nOldMove, array &$MoveList)
	{
		// Get the current move list and then extract the moves between the new and old
		// move in the order they are needed.
		$moves = $this->MoveList->GetMoveList();
		$nMoveOffset = $this->MoveList->GetStartingMovePlyNumber();

		if($moves == null || count($moves) == 0) return false;
		if ($nNewMove < $nMoveOffset || $nNewMove > count($moves) + $nMoveOffset ||
			$nOldMove < $nMoveOffset || $nOldMove > count($moves) + $nMoveOffset) return false;

		// Stepping back in the movelist.
		if ($nNewMove < $nOldMove)
		{
			for ($i = $nOldMove - $nMoveOffset; $i > $nNewMove - $nMoveOffset; $i--)
			{
				$MoveList[] = $moves[$i - 1];
			}
		}
		// Stepping forward in the movelist.
		else if ($nOldMove < $nNewMove)
		{
			for ($i = $nOldMove - $nMoveOffset; $i < $nNewMove - $nMoveOffset; $i++)
			{
				$MoveList[] = $moves[$i];
			}
		}
		
		return true;
	}

	/// <summary>
	/// Gets the move information at the given move ply number.
	/// </summary>
	/// <param name="nMovePly">The ply number for the move to get.</param>
	/// <returns>A MoveInfo struct holding information on the move requested. If move was not found, null is returned.</returns>
	public function GetMoveAtPly($nMovePly)
	{
		return $this->MoveList->GetMove($nMovePly);
	}

	/// <summary>
	/// Returns the ply move number for the starting state of the game.
	/// </summary>
	/// <returns>Returns the ply move number for the starting state of the game.</returns>
	public function GetStartingMoveNumber()
	{
		return $this->MoveList->GetStartingMovePlyNumber();
	}

	/// <summary>
	/// Returns the ply move number of the ending move.
	/// </summary>
	/// <returns>Returns the ply move number of the ending move.</returns>
	public function GetEndingMoveNumber()
	{
		return $this->MoveList->GetStartingMovePlyNumber() + $this->MoveList->MoveCount();
	}

	/// <summary>
	/// Returns the side that makes the first move.
	/// </summary>
	/// <returns>The side makes the first move. (0 - white, 1 - black)</returns>
	public function GetStartingSide()
	{
		return $this->MoveList->GetStartingSide();
	}

	/// <summary>
	/// The number of moves in the currently used variation.
	/// </summary>
	/// <returns>Returns the number of moves in the currently used variation.</returns>
	public function GetNumberOfMoves()
	{
		return $this->MoveList->MoveCount();
	}

	public function GetMoveList()
	{
		return $this->MoveList->GetMoveList();
	}
	
	/// <summary>
	/// Converts a move given by the start and end tiles, the type of piece and move type
	/// to a move in the SAN notation. Does not append + or # to the end based on the board state.
	/// </summary>
	/// <param name="nStartFile">The start file.</param>
	/// <param name="nStartRank">The start rank.</param>
	/// <param name="nEndFile">The end file.</param>
	/// <param name="nEndRank">The end rank.</param>
	/// <param name="pieceType">The type of the piece moved.</param>
	/// <param name="moveType">The type of move made.</param>
	/// <param name="promotionType">The type to which the piece got promoted.</param>
	/// <returns>A string representing the move in standard algebraic notation without the check or mate characters.</returns>
	public function ConvertMoveToSAN($nStartFile, $nStartRank, $nEndFile, $nEndRank, $pieceType, $moveType, $promotionType)
	{
		$fSameFile = false; $fSameRank = false; $fPawnCapture = false;

		// Check if the move was kingside or queenside casteling.
		if ($moveType == MOVE_TYPE::CASTLEK) return "O-O";
		if ($moveType == MOVE_TYPE::CASTLEQ) return "O-O-O";

		$szResult = $this->ChessUtils->ConvertIntegerTileToAlgebraicNotation($nEndFile + $nEndRank * 8);
		// If there is a promotion, add chars that identify the promotion piece type.
		if ($promotionType != PIECE_TYPE::UNDEFINED)
		{
			if ($promotionType == PIECE_TYPE::BISHOP) $szResult .= "=B";
			if ($promotionType == PIECE_TYPE::KNIGHT) $szResult .= "=N";
			if ($promotionType == PIECE_TYPE::QUEEN) $szResult .= "=Q";
			if ($promotionType == PIECE_TYPE::ROOK) $szResult .= "=R";
		}
		// Check if the move was a (en passant)capture and prepend a 'x' if it was.
		// Also note down if the move was made by a pawn because the starting file be
		// be prepend.
		if ($moveType == MOVE_TYPE::CAPTURED)
		{
			if ($this->Board[$nStartRank][$nStartFile] == PIECE_TYPE::PAWN ||
				$this->Board[$nStartRank][$nStartFile] == PIECE_TYPE::PAWN + 6)
				$fPawnCapture = true;
			$szResult = "x" . $szResult;
		}
		else if ($moveType == MOVE_TYPE::ENPASSANT)
		{
			$fPawnCapture = true;
			$szResult = "x" . $szResult;
		}
		// Check if any other piece(s) of that type belonging to the current side can move to
		// the end tile and see if their tile ranks or files are the same to the start tile.
		// This is needed to remove any ambiguity when determining the start tile.
		if ($this->CanOtherPieceMoveToTile($nEndFile + $nEndRank * 8, $nStartFile + $nStartRank * 8, $pieceType, $this->PlayerTurn, $fSameFile, $fSameRank))
		{
			// If piece(s) have the same rank and file then need to use rank and file.
			if ($fSameFile && $fSameRank)
			{
				if ($nStartRank == 0) $szResult = "1" . $szResult;
				else if ($nStartRank == 1) $szResult = "2" . $szResult;
				else if ($nStartRank == 2) $szResult = "3" . $szResult;
				else if ($nStartRank == 3) $szResult = "4" . $szResult;
				else if ($nStartRank == 4) $szResult = "5" . $szResult;
				else if ($nStartRank == 5) $szResult = "6" . $szResult;
				else if ($nStartRank == 6) $szResult = "7" . $szResult;
				else if ($nStartRank == 7) $szResult = "8" . $szResult;
				if ($nStartFile == 0) $szResult = "a" . $szResult;
				else if ($nStartFile == 1) $szResult = "b" . $szResult;
				else if ($nStartFile == 2) $szResult = "c" . $szResult;
				else if ($nStartFile == 3) $szResult = "d" . $szResult;
				else if ($nStartFile == 4) $szResult = "e" . $szResult;
				else if ($nStartFile == 5) $szResult = "f" . $szResult;
				else if ($nStartFile == 6) $szResult = "g" . $szResult;
				else if ($nStartFile == 7) $szResult = "h" . $szResult;
			}
			// If file is the same then need to use the rank to distinguish pieces.
			else if ($fSameFile)
			{
				if ($nStartRank == 0) $szResult = "1" . $szResult;
				else if ($nStartRank == 1) $szResult = "2" . $szResult;
				else if ($nStartRank == 2) $szResult = "3" . $szResult;
				else if ($nStartRank == 3) $szResult = "4" . $szResult;
				else if ($nStartRank == 4) $szResult = "5" . $szResult;
				else if ($nStartRank == 5) $szResult = "6" . $szResult;
				else if ($nStartRank == 6) $szResult = "7" . $szResult;
				else if ($nStartRank == 7) $szResult = "8" . $szResult;
			}
			// When the ranks are the same then need to use file to distinguish pieces.
			else if($fSameRank)
			{
				if ($nStartFile == 0) $szResult = "a" . $szResult;
				else if ($nStartFile == 1) $szResult = "b" . $szResult;
				else if ($nStartFile == 2) $szResult = "c" . $szResult;
				else if ($nStartFile == 3) $szResult = "d" . $szResult;
				else if ($nStartFile == 4) $szResult = "e" . $szResult;
				else if ($nStartFile == 5) $szResult = "f" . $szResult;
				else if ($nStartFile == 6) $szResult = "g" . $szResult;
				else if ($nStartFile == 7) $szResult = "h" . $szResult;
			}
			// Neither file nor rank are the same. Now depending on the start
			// and destination tiles, use either the rank when they are in
			// in different ranks, or the file when they are in different files.
			else
			{
				if ($nStartRank != $nEndRank)
				{
					if ($nStartRank == 0) $szResult = "1" . $szResult;
					else if ($nStartRank == 1) $szResult = "2" . $szResult;
					else if ($nStartRank == 2) $szResult = "3" . $szResult;
					else if ($nStartRank == 3) $szResult = "4" . $szResult;
					else if ($nStartRank == 4) $szResult = "5" . $szResult;
					else if ($nStartRank == 5) $szResult = "6" . $szResult;
					else if ($nStartRank == 6) $szResult = "7" . $szResult;
					else if ($nStartRank == 7) $szResult = "8" . $szResult;
				}
				else
				{
					if ($nStartFile == 0) $szResult = "a" . $szResult;
					else if ($nStartFile == 1) $szResult = "b" . $szResult;
					else if ($nStartFile == 2) $szResult = "c" . $szResult;
					else if ($nStartFile == 3) $szResult = "d" . $szResult;
					else if ($nStartFile == 4) $szResult = "e" . $szResult;
					else if ($nStartFile == 5) $szResult = "f" . $szResult;
					else if ($nStartFile == 6) $szResult = "g" . $szResult;
					else if ($nStartFile == 7) $szResult = "h" . $szResult;
				}
			}
		}
		else
		{
			// Move was unambiguous so check if a pawn capture occured and record the starting file
			if ($fPawnCapture)
			{
				if ($nStartFile == 0) $szResult = "a" . $szResult;
				else if ($nStartFile == 1) $szResult = "b" . $szResult;
				else if ($nStartFile == 2) $szResult = "c" . $szResult;
				else if ($nStartFile == 3) $szResult = "d" . $szResult;
				else if ($nStartFile == 4) $szResult = "e" . $szResult;
				else if ($nStartFile == 5) $szResult = "f" . $szResult;
				else if ($nStartFile == 6) $szResult = "g" . $szResult;
				else if ($nStartFile == 7) $szResult = "h" . $szResult;
			}
		}
		// Convert piece type to a letter and prepend it (except for pawns that don't need
		// a letter and when there was a promotion we know it was a pawn).
		if ($promotionType == PIECE_TYPE::UNDEFINED)
		{
			if ($pieceType == PIECE_TYPE::ROOK) $szResult = "R" . $szResult;
			else if ($pieceType == PIECE_TYPE::QUEEN) $szResult = "Q" . $szResult;
			else if ($pieceType == PIECE_TYPE::KNIGHT) $szResult = "N" . $szResult;
			else if ($pieceType == PIECE_TYPE::KING) $szResult = "K" . $szResult;
			else if ($pieceType == PIECE_TYPE::BISHOP) $szResult = "B" . $szResult;
		}

		return $szResult;
	}

	/// <summary>
	/// Checks if there are pieces of a certain type for the given player that can move
	/// to the given tile and sets the two flags to indicate if these other pieces are
	/// in the same rank and/or same file to the nCompareTile parameter.
	/// </summary>
	/// <param name="nMoveToTile">The tile that should be tested if it can be moved to.</param>
	/// <param name="nCompareTile">The tile to compare other piece's files/ranks with.</param>
	/// <param name="piecetype">The piece type to search for.</param>
	/// <param name="nPlayer">The player whose pieces should be checked.</param>
	/// <param name="fSameFile">Reference to a flag that will be set true if there are other pieces that can move to the MoveTo tile but which are in a different file to the nCompareTile parameter.</param>
	/// <param name="fSameRank">Reference to a flag that will be set true if there are other pieces that can move to the MoveTo tile but which are in a different rank to the nCompareTile parameter.</param>
	/// <returns>Returns true if there are other pieces on other tiles that can move to the given tile. Otherwise false is returned.</returns>
	public function CanOtherPieceMoveToTile($nMoveToTile, $nCompareTile, $piecetype,
		$nPlayer, &$fSameFile, &$fSameRank)
	{
		$this->nCalls++;
		$nSameFileCnt = 0; $nSameRankCnt = 0;
		$fFoundOtherPiece = false; $fChangedPlayer = false;
		$samePieces; $attackers;

		if ($nPlayer != $this->PlayerTurn)
		{
			$fChangedPlayer = true;
			$this->PlayerTurn = $nPlayer;
		}

		// Get a bitmap containing all the pieces of the same type and colour for the passed piecetype.
		if ($this->PlayerTurn == 0)
		{
			if ($piecetype == PIECE_TYPE::KING) $samePieces = $this->n64WKing;
			else if ($piecetype == PIECE_TYPE::BISHOP) $samePieces = $this->n64WBishops;
			else if ($piecetype == PIECE_TYPE::KNIGHT) $samePieces = $this->n64WKnights;
			else if ($piecetype == PIECE_TYPE::PAWN) $samePieces = $this->n64WPawns;
			else if ($piecetype == PIECE_TYPE::QUEEN) $samePieces = $this->n64WQueens;
			else if ($piecetype == PIECE_TYPE::ROOK) $samePieces = $this->n64WRooks;
		}
		else
		{
			if ($piecetype == PIECE_TYPE::KING) $samePieces = $this->n64BKing;
			else if ($piecetype == PIECE_TYPE::BISHOP) $samePieces = $this->n64BBishops;
			else if ($piecetype == PIECE_TYPE::KNIGHT) $samePieces = $this->n64BKnights;
			else if ($piecetype == PIECE_TYPE::PAWN) $samePieces = $this->n64BPawns;
			else if ($piecetype == PIECE_TYPE::QUEEN) $samePieces = $this->n64BQueens;
			else if ($piecetype == PIECE_TYPE::ROOK) $samePieces = $this->n64BRooks;
		}

		if ($piecetype == PIECE_TYPE::PAWN || $piecetype == PIECE_TYPE::PAWN + 6)
		{
			// If the destination tile is not occupied by an opponent piece then we know no other
			// pawn can move to this tile.
			if ($this->PlayerTurn == 0)
			{
				if (BitBoard::_AND_($this->n64BAll, $this->Tiles[$nMoveToTile])->is_zero())
				{
					$attackers = $this->Tiles[$nCompareTile]->duplicate();
				}
				else
				{
					$tmp = $this->FindAttacksToTile($this->floor[$nMoveToTile], $nMoveToTile % 8);
					$attackers = $tmp->_AND($samePieces)->_OR($this->Tiles[$nCompareTile]->duplicate());
				}
			}
			else
			{
				if (BitBoard::_AND_($this->n64WAll, $this->Tiles[$nMoveToTile])->is_zero())
				{
					$attackers = $this->Tiles[$nCompareTile]->duplicate();
				}
				else
				{
					$tmp = $this->FindAttacksToTile($this->floor[$nMoveToTile], $nMoveToTile % 8);
					$attackers = $tmp->_AND($samePieces)->_OR($this->Tiles[$nCompareTile]->duplicate());
				}
			}
		}
		else
		{
			// Get a bitmap of all pieces attacking the given tile and then mask it with the 
			// position(s) of the selected piece type.
			$tmp = $this->FindAttacksToTile($this->floor[$nMoveToTile], $nMoveToTile % 8);
			$attackers = $tmp->_AND($samePieces);
		}
		// Remove the tile we want to compare to from the attackers bitmap
		$attackers->_XOR($this->Tiles[$nCompareTile]);

		if (!$attackers->is_zero())
		{
			// loop through all the tiles and test if tiles are on a different rank
			// or file or both.
			$tmp = $this->ChessUtils->GetTilesFromBitmap($attackers);
			// Go through all the pieces.
			foreach ($tmp as $tile)
			{
				if ($tile % 8 == $nCompareTile % 8) $nSameFileCnt++;
				if (floor($tile / 8) == floor($nCompareTile / 8)) $nSameRankCnt++;
				$fFoundOtherPiece = true;
			}

			// If the file counter is > 0 then that means there is a piece that can move to MoveToTile
			// and is in the same file. Same for the rank counter.
			if ($nSameFileCnt > 0) $fSameFile = true;
			if ($nSameRankCnt > 0) $fSameRank = true;
		}

		// Change back the player turn if it was changed at the start of the function.
		if ($fChangedPlayer)
		{
			$this->PlayerTurn = ($this->PlayerTurn == 0) ? 1 : 0;
			$this->PlayerTurn = $nPlayer;
		}

		return $fSameRank || $fSameFile || $fFoundOtherPiece;
	}

	public function UpdateMoveListControl(&$moveLister)
	{
		//moveLister.UpdateMoveList($this->MoveList->GetMoveList());
	}

	public function GetBoardPiecesInByteArray()
	{
		// byte[] rgPieces = new byte[64];
		// for (int i = 0; i < 64; i++)
		// {
			// rgPieces[i] = (byte)$this->Board[i / 8, i % 8];
		// }
		// return rgPieces;
	}


	/// <summary>
	/// Restores the state of the board to what it was in a recorded move.
	/// </summary>
	/// <param name="nMovePlyNumber">The ply number identifying the move to restore to</param>
	/// <returns>Returns true if the state could be restored.</returns>
	private function RestoreBoardStateFromRecordedMove($move, $nFullMoveNumber)
	{
		//MoveInfo move = $this->MoveList.GetMove(nMovePlyNumber);
		//if (move == null) return false;
		
		// Set the bitboard values.
		$this->n64BBishops = $move->Boards->n64BBishops->duplicate();
		$this->n64BlackCastle = $move->Boards->n64BCastling->duplicate();
		$this->n64BKing = $move->Boards->n64BKing->duplicate();
		$this->n64BKnights = $move->Boards->n64BKnights->duplicate();
		$this->n64BPawns = $move->Boards->n64BPawns->duplicate();
		$this->n64BQueens = $move->Boards->n64BQueens->duplicate();
		$this->n64BRooks = $move->Boards->n64BRooks->duplicate();
		$this->n64Enpassant = $move->Boards->n64EnPassant->duplicate();
		$this->n64WBishops = $move->Boards->n64WBishops->duplicate();
		$this->n64WhiteCastle = $move->Boards->n64WCastling->duplicate();
		$this->n64WKing = $move->Boards->n64WKing->duplicate();
		$this->n64WKnights = $move->Boards->n64WKnights->duplicate();
		$this->n64WPawns = $move->Boards->n64WPawns->duplicate();
		$this->n64WQueens = $move->Boards->n64WQueens->duplicate();
		$this->n64WRooks = $move->Boards->n64WRooks->duplicate();
		$this->n64Enpassant = $move->Boards->n64EnPassant->duplicate();
		$this->n64WhiteCastle = $move->Boards->n64WCastling->duplicate();
		$this->n64BlackCastle = $move->Boards->n64BCastling->duplicate();

		$this->n64BAll = $this->n64BBishops | $this->n64BKing | $this->n64BKnights | $this->n64BPawns | $this->n64BQueens | $this->n64BRooks;
		$this->n64WAll = $this->n64WBishops | $this->n64WKing | $this->n64WKnights | $this->n64WPawns | $this->n64WQueens | $this->n64WRooks;
		$this->n64All = $this->n64BAll | $this->n64WAll;

		// State variables
		$this->nEnPassantSquare = move.nEnPassantTile;
		$this->fCastleBKingside = move.fBCastleKSide;
		$this->fCastleBQueenside = move.fBCastleQSide;
		$this->fCastleWKingside = move.fWCastleKSide;
		$this->fCastleWQueenside = move.fWCastleQSide;
		$this->PlayerTurn = move.nSideMoved == 0 ? 1 : 0;
		$this->nHalfMoveCounter = move.nHalfMoveClock;
		$this->nFullMoveCounter = ($this->MoveList->MoveCount() + 1) / 2 + 1;

		// Set the pieces in the board array.
		for ($i = 0; $i < 8; $i++){
			for ($j = 0; $j < 8; $j++){
				$this->Board[$i][$j] = PIECE_TYPE::UNDEFINED;
			}
		}	
				
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BBishops);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::BISHOP + 6;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::KING + 6;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BKnights);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::KNIGHT + 6;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BPawns);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::PAWN + 6;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BQueens);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::QUEEN  + 6;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BRooks);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::ROOK + 6;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WBishops);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::BISHOP;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::KING;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WKnights);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::KNIGHT;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WPawns);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::PAWN;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WQueens);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::QUEEN;
		$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WRooks);
		foreach ($tiles as $tile)
			$this->Board[$this->floor[$tile]][$tile % 8] = PIECE_TYPE::ROOK;

		return true;
	}

	/// <summary>
	/// Takes the current game position and produces a FEN from it.
	/// </summary>
	/// <returns>Returns the current game state as a FEN.</returns>
	public function GetFENForCurrentPosition()
	{
		$nSpaceCnt = 0;
		$szFEN = "";
		$cLetter;

		// Start from rank 8 and note down pieces and spaces as they are found.
		for ($rank = 7; $rank > -1; $rank--)
		{
			for ($file = 0; $file < 8; $file++)
			{
				$cLetter = '.';
				if ($this->Board[$rank][$file] == PIECE_TYPE::KING) $cLetter = 'K';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::QUEEN) $cLetter = 'Q';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::ROOK) $cLetter = 'R';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::BISHOP) $cLetter = 'B';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::KNIGHT) $cLetter = 'N';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::PAWN) $cLetter = 'P';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::KING + 6) $cLetter = 'k';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::QUEEN + 6) $cLetter = 'q';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::ROOK + 6) $cLetter = 'r';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::BISHOP + 6) $cLetter = 'b';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::KNIGHT + 6) $cLetter = 'n';
				else if ($this->Board[$rank][$file] == PIECE_TYPE::PAWN + 6) $cLetter = 'p';
				if ($nSpaceCnt != 0 && $cLetter != '.')
				{
					$szFEN .= $nSpaceCnt;
					$nSpaceCnt = 0;
					$szFEN .= $cLetter;
				}
				else
				{
					if ($cLetter != '.')
						$szFEN .= $cLetter;
					else
						$nSpaceCnt++;
				}
			}
			if ($nSpaceCnt != 0)
			{
				$szFEN .= $nSpaceCnt;
				$nSpaceCnt = 0;
			}
			if($rank != 0) $szFEN .= "/";
		}

		// Append the state for various things like castling possible, turn, move number, etc..
		if ($this->PlayerTurn == 0) $szFEN .= " w ";
		if ($this->PlayerTurn == 1) $szFEN .= " b ";

		if ($this->fCastleWKingside) $szFEN .= "K";
		if ($this->fCastleWQueenside) $szFEN .= "Q";
		if ($this->fCastleBKingside) $szFEN .= "k";
		if ($this->fCastleBQueenside) $szFEN .= "q";
		if (!$this->fCastleBKingside && !$this->fCastleBQueenside && !$this->fCastleWKingside && !$this->fCastleWQueenside)
			$szFEN .= "-";

		if ($this->nEnPassantSquare == -1) $szFEN .= " -";
		else $szFEN .= " " . $this->ChessUtils->ConvertIntegerTileToAlgebraicNotation($this->nEnPassantSquare);

		$szFEN .= " " . $this->nHalfMoveCounter;
		$szFEN .= " " . $this->nFullMoveCounter; 

		return $szFEN;
	}

	/// <summary>
	/// Returns a flag indicating if the board was setup using a FEN.
	/// </summary>
	/// <returns>Returns a flag indicating if the board was setup using a FEN.</returns>
	public function UsedFEN()
	{
		return $this->fUsedFEN;
	}

	/// <summary>
	/// Creates a list of all move variations in the movelist (the whole move list tree) for this game and returns
	/// that list. 
	/// </summary>
	/// <returns>A list containing all the moves and variations for this game.</returns>
	public function GetCompleteMovelist()
	{
		// List<PGNMoveInfo2> CompleteMoveList = new List<PGNMoveInfo2>();
		// // Get the move lists for all variations starting at the first move.
		// ProcessMoveLists($this->MoveList->GetMove(0), ref CompleteMoveList);
		// return CompleteMoveList;
	}

	/// <summary>
	/// Records all moves into a PGNMoveInfo List starting from the given move's variation(s).
	/// </summary>
	/// <param name="move">The move info variable from which to traverse all variations.</param>
	/// <param name="PGNMoveList">A move list to append the next move/variation lists to.</param>
	// private function ProcessMoveLists(MoveInfo move, ref List<PGNMoveInfo2> PGNMoveList)
	// {
		// //List<PGNMoveInfo2> newBranch = null;
		// PGNMoveInfo2 newMove;
		// List<List<PGNMoveInfo2>> moveVarReference;
		// MoveInfo curMove;

		// // If there are no variation(s) for the next move then its the end for this current variation
		// if (move.Variations == null) return;
		// curMove = move;
		// // For every variation collect the moves. The first variation is the primary variation
		// // and its moves are added to the maim variation list while the following variation
		// // lists are added to a list of move lists.
		// for (int i = 0; i < move.Variations.Count; i++)
		// {
			
			// // Loop as long as there are more moves.
			// while (curMove.Variations != null && curMove.Variations.Count > 0)
			// {
				// curMove = curMove.Variations[i];
				// // Record the current move.
				// if (i == 0)
				// {
					// // Primary move list in this branch.
					// newMove = new PGNMoveInfo2();
					// newMove.Comment = System.Text.ASCIIEncoding.ASCII.GetBytes(curMove.szComment);
					// newMove.SANMove = System.Text.ASCIIEncoding.ASCII.GetBytes(curMove.szSAN);
					// newMove.nSide = curMove.nSideMoved == ENUMS.PlayerSide.White ? 0 : 1;
					// if (PGNMoveList  == null)
						// PGNMoveList = new List<PGNMoveInfo2>();
					// PGNMoveList.Add(newMove);
				// }
				// else
				// {
					// // Add the move to the correct variation list.
					// newMove = new PGNMoveInfo2();
					// newMove.Comment = System.Text.ASCIIEncoding.ASCII.GetBytes(curMove.szComment);
					// newMove.SANMove = System.Text.ASCIIEncoding.ASCII.GetBytes(curMove.szSAN);
					// newMove.nSide = curMove.nSideMoved == ENUMS.PlayerSide.White ? 0 : 1;
					// moveVarReference = PGNMoveList[0].rgVariations;
					// if (moveVarReference == null)
						// moveVarReference = new List<List<PGNMoveInfo2>>();
					// if (moveVarReference.Count < i)
						// moveVarReference.Add(new List<PGNMoveInfo2>());
					// PGNMoveList[0].rgVariations[i - 1].Add(newMove);
				// }

				// // If there are two or move moves available next, then call this function again
				// // so it can append the variations to the currently used list.
				// //if (curMove.Variations != null && curMove.Variations.Count > 1)
				// //{
				// //    if (i == 0)
				// //    {
				// //        GetMovesBranch(curMove, ref PGNMoveList[PGNMoveList.Count - 1]);
				// //    }
				// //    else
				// //    {
				// //        GetMovesBranch(curMove, ref PGNMoveList[PGNMoveList.Count - 1].rgVariations[i - 1]);
				// //        //newBranch = GetMovesBranch(curMove);
				// //        //if (newBranch != null)
				// //        //{
				// //        //   // if (BranchMoves[0].rgVariations == null)
				// //        //   //     BranchMoves[0].rgVariations = new List<List<PGNMoveInfo2>>();
				// //        //   // BranchMoves[0].rgVariations.Add(newBranch);
				// //        //}
				// //    }
				// //}
				
			// }
			
		// }
		// //PGNMoveInfo2 newMove = new PGNMoveInfo2();
		// //List<PGNMoveInfo2> branchMoves = null;
		// //MoveInfo curMove;

		// //if (move.Variations == null || move.Variations.Count == 0) return null;

		// //// Go through all variations.
		// //for (int i = 0; i < move.Variations.Count; i++)
		// //{
		// //    if (move.szSAN == null || move.szSAN == "") return branchMoves;

		// //    branchMoves = new List<PGNMoveInfo2>();
		// //    newMove.Comment = System.Text.ASCIIEncoding.ASCII.GetBytes(move.szComment);
		// //    newMove.SANMove = System.Text.ASCIIEncoding.ASCII.GetBytes(move.szSAN);
		// //    newMove.nSide = move.nSideMoved;

		// //    foreach (MoveInfo branch in move.Variations)
		// //    {
		// //        // First comes the primary variation. Once that has been received
		// //        // additional move variations are added to the variations list.
		// //        if (MovesList == null)
		// //        {
		// //            MovesList = GetMovesBranch(branch);
		// //        }
		// //        else
		// //        {
					
		// //        }
		// //    }
		// //}


	// }

	/// <summary>
	/// Checks if the current board position (with en passant/castle states) has been repeated 2 other times.
	/// </summary>
	/// <returns>True if 3 fold repetition has occurred; otherwise false.</returns>
	public function CheckCurrentMoveFor3FoldRepetition()
	{
		return $this->MoveList->CheckCurrentMoveFor3FoldRepetition();
	}

	/// <summary>
	/// Sets up the squares that can be attacked for each piece type for all 64 positions on the board
	/// as well as populating some other bit boards.
	/// </summary>
	// Hard coded values which is faster than calculating the bitboards at runtime.
	private function SetupAttackBitboards()
	{

// Generate masks for every tile. Helps to reduce the number of bit shifting operations required.
$this->Tiles[0] = $bb = new BitBoard(); $bb->num4 = 1;
$this->Tiles[1] = $bb = new BitBoard(); $bb->num4 = 2;
$this->Tiles[2] = $bb = new BitBoard(); $bb->num4 = 4;
$this->Tiles[3] = $bb = new BitBoard(); $bb->num4 = 8;
$this->Tiles[4] = $bb = new BitBoard(); $bb->num4 = 16;
$this->Tiles[5] = $bb = new BitBoard(); $bb->num4 = 32;
$this->Tiles[6] = $bb = new BitBoard(); $bb->num4 = 64;
$this->Tiles[7] = $bb = new BitBoard(); $bb->num4 = 128;
$this->Tiles[8] = $bb = new BitBoard(); $bb->num4 = 256;
$this->Tiles[9] = $bb = new BitBoard(); $bb->num4 = 512;
$this->Tiles[10] = $bb = new BitBoard(); $bb->num4 = 1024;
$this->Tiles[11] = $bb = new BitBoard(); $bb->num4 = 2048;
$this->Tiles[12] = $bb = new BitBoard(); $bb->num4 = 4096;
$this->Tiles[13] = $bb = new BitBoard(); $bb->num4 = 8192;
$this->Tiles[14] = $bb = new BitBoard(); $bb->num4 = 16384;
$this->Tiles[15] = $bb = new BitBoard(); $bb->num4 = 32768;
$this->Tiles[16] = $bb = new BitBoard(); $bb->num3 = 1; 
$this->Tiles[17] = $bb = new BitBoard(); $bb->num3 = 2; 
$this->Tiles[18] = $bb = new BitBoard(); $bb->num3 = 4; 
$this->Tiles[19] = $bb = new BitBoard(); $bb->num3 = 8; 
$this->Tiles[20] = $bb = new BitBoard(); $bb->num3 = 16; 
$this->Tiles[21] = $bb = new BitBoard(); $bb->num3 = 32; 
$this->Tiles[22] = $bb = new BitBoard(); $bb->num3 = 64; 
$this->Tiles[23] = $bb = new BitBoard(); $bb->num3 = 128; 
$this->Tiles[24] = $bb = new BitBoard(); $bb->num3 = 256; 
$this->Tiles[25] = $bb = new BitBoard(); $bb->num3 = 512; 
$this->Tiles[26] = $bb = new BitBoard(); $bb->num3 = 1024; 
$this->Tiles[27] = $bb = new BitBoard(); $bb->num3 = 2048; 
$this->Tiles[28] = $bb = new BitBoard(); $bb->num3 = 4096; 
$this->Tiles[29] = $bb = new BitBoard(); $bb->num3 = 8192; 
$this->Tiles[30] = $bb = new BitBoard(); $bb->num3 = 16384; 
$this->Tiles[31] = $bb = new BitBoard(); $bb->num3 = 32768; 
$this->Tiles[32] = $bb = new BitBoard(); $bb->num2 = 1; 
$this->Tiles[33] = $bb = new BitBoard(); $bb->num2 = 2; 
$this->Tiles[34] = $bb = new BitBoard(); $bb->num2 = 4; 
$this->Tiles[35] = $bb = new BitBoard(); $bb->num2 = 8; 
$this->Tiles[36] = $bb = new BitBoard(); $bb->num2 = 16; 
$this->Tiles[37] = $bb = new BitBoard(); $bb->num2 = 32; 
$this->Tiles[38] = $bb = new BitBoard(); $bb->num2 = 64; 
$this->Tiles[39] = $bb = new BitBoard(); $bb->num2 = 128; 
$this->Tiles[40] = $bb = new BitBoard(); $bb->num2 = 256; 
$this->Tiles[41] = $bb = new BitBoard(); $bb->num2 = 512; 
$this->Tiles[42] = $bb = new BitBoard(); $bb->num2 = 1024; 
$this->Tiles[43] = $bb = new BitBoard(); $bb->num2 = 2048; 
$this->Tiles[44] = $bb = new BitBoard(); $bb->num2 = 4096; 
$this->Tiles[45] = $bb = new BitBoard(); $bb->num2 = 8192; 
$this->Tiles[46] = $bb = new BitBoard(); $bb->num2 = 16384; 
$this->Tiles[47] = $bb = new BitBoard(); $bb->num2 = 32768; 
$this->Tiles[48] = $bb = new BitBoard(); $bb->num1 = 1; 
$this->Tiles[49] = $bb = new BitBoard(); $bb->num1 = 2; 
$this->Tiles[50] = $bb = new BitBoard(); $bb->num1 = 4; 
$this->Tiles[51] = $bb = new BitBoard(); $bb->num1 = 8; 
$this->Tiles[52] = $bb = new BitBoard(); $bb->num1 = 16; 
$this->Tiles[53] = $bb = new BitBoard(); $bb->num1 = 32; 
$this->Tiles[54] = $bb = new BitBoard(); $bb->num1 = 64; 
$this->Tiles[55] = $bb = new BitBoard(); $bb->num1 = 128; 
$this->Tiles[56] = $bb = new BitBoard(); $bb->num1 = 256; 
$this->Tiles[57] = $bb = new BitBoard(); $bb->num1 = 512; 
$this->Tiles[58] = $bb = new BitBoard(); $bb->num1 = 1024; 
$this->Tiles[59] = $bb = new BitBoard(); $bb->num1 = 2048; 
$this->Tiles[60] = $bb = new BitBoard(); $bb->num1 = 4096; 
$this->Tiles[61] = $bb = new BitBoard(); $bb->num1 = 8192; 
$this->Tiles[62] = $bb = new BitBoard(); $bb->num1 = 16384; 
$this->Tiles[63] = $bb = new BitBoard(); $bb->num1 = 32768; 

// The king can move 1 square in any direction. Of course it cannot move off the board :P
$this->KingAttacks[0] = $bb = new BitBoard(); $bb->num4 = 770;
$this->KingAttacks[1] = $bb = new BitBoard(); $bb->num4 = 1797;
$this->KingAttacks[2] = $bb = new BitBoard(); $bb->num4 = 3594;
$this->KingAttacks[3] = $bb = new BitBoard(); $bb->num4 = 7188;
$this->KingAttacks[4] = $bb = new BitBoard(); $bb->num4 = 14376;
$this->KingAttacks[5] = $bb = new BitBoard(); $bb->num4 = 28752;
$this->KingAttacks[6] = $bb = new BitBoard(); $bb->num4 = 57504;
$this->KingAttacks[7] = $bb = new BitBoard(); $bb->num4 = 49216;
$this->KingAttacks[8] = $bb = new BitBoard(); $bb->num3 = 3; $bb->num4 = 515;
$this->KingAttacks[9] = $bb = new BitBoard(); $bb->num3 = 7; $bb->num4 = 1287;
$this->KingAttacks[10] = $bb = new BitBoard(); $bb->num3 = 14; $bb->num4 = 2574;
$this->KingAttacks[11] = $bb = new BitBoard(); $bb->num3 = 28; $bb->num4 = 5148;
$this->KingAttacks[12] = $bb = new BitBoard(); $bb->num3 = 56; $bb->num4 = 10296;
$this->KingAttacks[13] = $bb = new BitBoard(); $bb->num3 = 112; $bb->num4 = 20592;
$this->KingAttacks[14] = $bb = new BitBoard(); $bb->num3 = 224; $bb->num4 = 41184;
$this->KingAttacks[15] = $bb = new BitBoard(); $bb->num3 = 192; $bb->num4 = 16576;
$this->KingAttacks[16] = $bb = new BitBoard(); $bb->num3 = 770; $bb->num4 = 768;
$this->KingAttacks[17] = $bb = new BitBoard(); $bb->num3 = 1797; $bb->num4 = 1792;
$this->KingAttacks[18] = $bb = new BitBoard(); $bb->num3 = 3594; $bb->num4 = 3584;
$this->KingAttacks[19] = $bb = new BitBoard(); $bb->num3 = 7188; $bb->num4 = 7168;
$this->KingAttacks[20] = $bb = new BitBoard(); $bb->num3 = 14376; $bb->num4 = 14336;
$this->KingAttacks[21] = $bb = new BitBoard(); $bb->num3 = 28752; $bb->num4 = 28672;
$this->KingAttacks[22] = $bb = new BitBoard(); $bb->num3 = 57504; $bb->num4 = 57344;
$this->KingAttacks[23] = $bb = new BitBoard(); $bb->num3 = 49216; $bb->num4 = 49152;
$this->KingAttacks[24] = $bb = new BitBoard(); $bb->num2 = 3; $bb->num3 = 515; 
$this->KingAttacks[25] = $bb = new BitBoard(); $bb->num2 = 7; $bb->num3 = 1287; 
$this->KingAttacks[26] = $bb = new BitBoard(); $bb->num2 = 14; $bb->num3 = 2574; 
$this->KingAttacks[27] = $bb = new BitBoard(); $bb->num2 = 28; $bb->num3 = 5148; 
$this->KingAttacks[28] = $bb = new BitBoard(); $bb->num2 = 56; $bb->num3 = 10296; 
$this->KingAttacks[29] = $bb = new BitBoard(); $bb->num2 = 112; $bb->num3 = 20592; 
$this->KingAttacks[30] = $bb = new BitBoard(); $bb->num2 = 224; $bb->num3 = 41184; 
$this->KingAttacks[31] = $bb = new BitBoard(); $bb->num2 = 192; $bb->num3 = 16576; 
$this->KingAttacks[32] = $bb = new BitBoard(); $bb->num2 = 770; $bb->num3 = 768; 
$this->KingAttacks[33] = $bb = new BitBoard(); $bb->num2 = 1797; $bb->num3 = 1792; 
$this->KingAttacks[34] = $bb = new BitBoard(); $bb->num2 = 3594; $bb->num3 = 3584; 
$this->KingAttacks[35] = $bb = new BitBoard(); $bb->num2 = 7188; $bb->num3 = 7168; 
$this->KingAttacks[36] = $bb = new BitBoard(); $bb->num2 = 14376; $bb->num3 = 14336; 
$this->KingAttacks[37] = $bb = new BitBoard(); $bb->num2 = 28752; $bb->num3 = 28672; 
$this->KingAttacks[38] = $bb = new BitBoard(); $bb->num2 = 57504; $bb->num3 = 57344; 
$this->KingAttacks[39] = $bb = new BitBoard(); $bb->num2 = 49216; $bb->num3 = 49152; 
$this->KingAttacks[40] = $bb = new BitBoard(); $bb->num1 = 3; $bb->num2 = 515; 
$this->KingAttacks[41] = $bb = new BitBoard(); $bb->num1 = 7; $bb->num2 = 1287; 
$this->KingAttacks[42] = $bb = new BitBoard(); $bb->num1 = 14; $bb->num2 = 2574; 
$this->KingAttacks[43] = $bb = new BitBoard(); $bb->num1 = 28; $bb->num2 = 5148; 
$this->KingAttacks[44] = $bb = new BitBoard(); $bb->num1 = 56; $bb->num2 = 10296; 
$this->KingAttacks[45] = $bb = new BitBoard(); $bb->num1 = 112; $bb->num2 = 20592; 
$this->KingAttacks[46] = $bb = new BitBoard(); $bb->num1 = 224; $bb->num2 = 41184; 
$this->KingAttacks[47] = $bb = new BitBoard(); $bb->num1 = 192; $bb->num2 = 16576; 
$this->KingAttacks[48] = $bb = new BitBoard(); $bb->num1 = 770; $bb->num2 = 768; 
$this->KingAttacks[49] = $bb = new BitBoard(); $bb->num1 = 1797; $bb->num2 = 1792; 
$this->KingAttacks[50] = $bb = new BitBoard(); $bb->num1 = 3594; $bb->num2 = 3584; 
$this->KingAttacks[51] = $bb = new BitBoard(); $bb->num1 = 7188; $bb->num2 = 7168; 
$this->KingAttacks[52] = $bb = new BitBoard(); $bb->num1 = 14376; $bb->num2 = 14336; 
$this->KingAttacks[53] = $bb = new BitBoard(); $bb->num1 = 28752; $bb->num2 = 28672; 
$this->KingAttacks[54] = $bb = new BitBoard(); $bb->num1 = 57504; $bb->num2 = 57344; 
$this->KingAttacks[55] = $bb = new BitBoard(); $bb->num1 = 49216; $bb->num2 = 49152; 
$this->KingAttacks[56] = $bb = new BitBoard(); $bb->num1 = 515; 
$this->KingAttacks[57] = $bb = new BitBoard(); $bb->num1 = 1287; 
$this->KingAttacks[58] = $bb = new BitBoard(); $bb->num1 = 2574; 
$this->KingAttacks[59] = $bb = new BitBoard(); $bb->num1 = 5148; 
$this->KingAttacks[60] = $bb = new BitBoard(); $bb->num1 = 10296; 
$this->KingAttacks[61] = $bb = new BitBoard(); $bb->num1 = 20592; 
$this->KingAttacks[62] = $bb = new BitBoard(); $bb->num1 = 41184; 
$this->KingAttacks[63] = $bb = new BitBoard(); $bb->num1 = 16576; 

// Knight moves.
$this->KnightAttacks[0] = $bb = new BitBoard(); $bb->num3 = 2; $bb->num4 = 1024;
$this->KnightAttacks[1] = $bb = new BitBoard(); $bb->num3 = 5; $bb->num4 = 2048;
$this->KnightAttacks[2] = $bb = new BitBoard(); $bb->num3 = 10; $bb->num4 = 4352;
$this->KnightAttacks[3] = $bb = new BitBoard(); $bb->num3 = 20; $bb->num4 = 8704;
$this->KnightAttacks[4] = $bb = new BitBoard(); $bb->num3 = 40; $bb->num4 = 17408;
$this->KnightAttacks[5] = $bb = new BitBoard(); $bb->num3 = 80; $bb->num4 = 34816;
$this->KnightAttacks[6] = $bb = new BitBoard(); $bb->num3 = 160; $bb->num4 = 4096;
$this->KnightAttacks[7] = $bb = new BitBoard(); $bb->num3 = 64; $bb->num4 = 8192;
$this->KnightAttacks[8] = $bb = new BitBoard(); $bb->num3 = 516; $bb->num4 = 4;
$this->KnightAttacks[9] = $bb = new BitBoard(); $bb->num3 = 1288; $bb->num4 = 8;
$this->KnightAttacks[10] = $bb = new BitBoard(); $bb->num3 = 2577; $bb->num4 = 17;
$this->KnightAttacks[11] = $bb = new BitBoard(); $bb->num3 = 5154; $bb->num4 = 34;
$this->KnightAttacks[12] = $bb = new BitBoard(); $bb->num3 = 10308; $bb->num4 = 68;
$this->KnightAttacks[13] = $bb = new BitBoard(); $bb->num3 = 20616; $bb->num4 = 136;
$this->KnightAttacks[14] = $bb = new BitBoard(); $bb->num3 = 40976; $bb->num4 = 16;
$this->KnightAttacks[15] = $bb = new BitBoard(); $bb->num3 = 16416; $bb->num4 = 32;
$this->KnightAttacks[16] = $bb = new BitBoard(); $bb->num2 = 2; $bb->num3 = 1024; $bb->num4 = 1026;
$this->KnightAttacks[17] = $bb = new BitBoard(); $bb->num2 = 5; $bb->num3 = 2048; $bb->num4 = 2053;
$this->KnightAttacks[18] = $bb = new BitBoard(); $bb->num2 = 10; $bb->num3 = 4352; $bb->num4 = 4362;
$this->KnightAttacks[19] = $bb = new BitBoard(); $bb->num2 = 20; $bb->num3 = 8704; $bb->num4 = 8724;
$this->KnightAttacks[20] = $bb = new BitBoard(); $bb->num2 = 40; $bb->num3 = 17408; $bb->num4 = 17448;
$this->KnightAttacks[21] = $bb = new BitBoard(); $bb->num2 = 80; $bb->num3 = 34816; $bb->num4 = 34896;
$this->KnightAttacks[22] = $bb = new BitBoard(); $bb->num2 = 160; $bb->num3 = 4096; $bb->num4 = 4256;
$this->KnightAttacks[23] = $bb = new BitBoard(); $bb->num2 = 64; $bb->num3 = 8192; $bb->num4 = 8256;
$this->KnightAttacks[24] = $bb = new BitBoard(); $bb->num2 = 516; $bb->num3 = 4; $bb->num4 = 512;
$this->KnightAttacks[25] = $bb = new BitBoard(); $bb->num2 = 1288; $bb->num3 = 8; $bb->num4 = 1280;
$this->KnightAttacks[26] = $bb = new BitBoard(); $bb->num2 = 2577; $bb->num3 = 17; $bb->num4 = 2560;
$this->KnightAttacks[27] = $bb = new BitBoard(); $bb->num2 = 5154; $bb->num3 = 34; $bb->num4 = 5120;
$this->KnightAttacks[28] = $bb = new BitBoard(); $bb->num2 = 10308; $bb->num3 = 68; $bb->num4 = 10240;
$this->KnightAttacks[29] = $bb = new BitBoard(); $bb->num2 = 20616; $bb->num3 = 136; $bb->num4 = 20480;
$this->KnightAttacks[30] = $bb = new BitBoard(); $bb->num2 = 40976; $bb->num3 = 16; $bb->num4 = 40960;
$this->KnightAttacks[31] = $bb = new BitBoard(); $bb->num2 = 16416; $bb->num3 = 32; $bb->num4 = 16384;
$this->KnightAttacks[32] = $bb = new BitBoard(); $bb->num1 = 2; $bb->num2 = 1024; $bb->num3 = 1026; 
$this->KnightAttacks[33] = $bb = new BitBoard(); $bb->num1 = 5; $bb->num2 = 2048; $bb->num3 = 2053; 
$this->KnightAttacks[34] = $bb = new BitBoard(); $bb->num1 = 10; $bb->num2 = 4352; $bb->num3 = 4362; 
$this->KnightAttacks[35] = $bb = new BitBoard(); $bb->num1 = 20; $bb->num2 = 8704; $bb->num3 = 8724; 
$this->KnightAttacks[36] = $bb = new BitBoard(); $bb->num1 = 40; $bb->num2 = 17408; $bb->num3 = 17448; 
$this->KnightAttacks[37] = $bb = new BitBoard(); $bb->num1 = 80; $bb->num2 = 34816; $bb->num3 = 34896; 
$this->KnightAttacks[38] = $bb = new BitBoard(); $bb->num1 = 160; $bb->num2 = 4096; $bb->num3 = 4256; 
$this->KnightAttacks[39] = $bb = new BitBoard(); $bb->num1 = 64; $bb->num2 = 8192; $bb->num3 = 8256; 
$this->KnightAttacks[40] = $bb = new BitBoard(); $bb->num1 = 516; $bb->num2 = 4; $bb->num3 = 512; 
$this->KnightAttacks[41] = $bb = new BitBoard(); $bb->num1 = 1288; $bb->num2 = 8; $bb->num3 = 1280; 
$this->KnightAttacks[42] = $bb = new BitBoard(); $bb->num1 = 2577; $bb->num2 = 17; $bb->num3 = 2560; 
$this->KnightAttacks[43] = $bb = new BitBoard(); $bb->num1 = 5154; $bb->num2 = 34; $bb->num3 = 5120; 
$this->KnightAttacks[44] = $bb = new BitBoard(); $bb->num1 = 10308; $bb->num2 = 68; $bb->num3 = 10240; 
$this->KnightAttacks[45] = $bb = new BitBoard(); $bb->num1 = 20616; $bb->num2 = 136; $bb->num3 = 20480; 
$this->KnightAttacks[46] = $bb = new BitBoard(); $bb->num1 = 40976; $bb->num2 = 16; $bb->num3 = 40960; 
$this->KnightAttacks[47] = $bb = new BitBoard(); $bb->num1 = 16416; $bb->num2 = 32; $bb->num3 = 16384; 
$this->KnightAttacks[48] = $bb = new BitBoard(); $bb->num1 = 1024; $bb->num2 = 1026; 
$this->KnightAttacks[49] = $bb = new BitBoard(); $bb->num1 = 2048; $bb->num2 = 2053; 
$this->KnightAttacks[50] = $bb = new BitBoard(); $bb->num1 = 4352; $bb->num2 = 4362; 
$this->KnightAttacks[51] = $bb = new BitBoard(); $bb->num1 = 8704; $bb->num2 = 8724; 
$this->KnightAttacks[52] = $bb = new BitBoard(); $bb->num1 = 17408; $bb->num2 = 17448; 
$this->KnightAttacks[53] = $bb = new BitBoard(); $bb->num1 = 34816; $bb->num2 = 34896; 
$this->KnightAttacks[54] = $bb = new BitBoard(); $bb->num1 = 4096; $bb->num2 = 4256; 
$this->KnightAttacks[55] = $bb = new BitBoard(); $bb->num1 = 8192; $bb->num2 = 8256; 
$this->KnightAttacks[56] = $bb = new BitBoard(); $bb->num1 = 4; $bb->num2 = 512; 
$this->KnightAttacks[57] = $bb = new BitBoard(); $bb->num1 = 8; $bb->num2 = 1280; 
$this->KnightAttacks[58] = $bb = new BitBoard(); $bb->num1 = 17; $bb->num2 = 2560; 
$this->KnightAttacks[59] = $bb = new BitBoard(); $bb->num1 = 34; $bb->num2 = 5120; 
$this->KnightAttacks[60] = $bb = new BitBoard(); $bb->num1 = 68; $bb->num2 = 10240; 
$this->KnightAttacks[61] = $bb = new BitBoard(); $bb->num1 = 136; $bb->num2 = 20480; 
$this->KnightAttacks[62] = $bb = new BitBoard(); $bb->num1 = 16; $bb->num2 = 40960; 
$this->KnightAttacks[63] = $bb = new BitBoard(); $bb->num1 = 32; $bb->num2 = 16384; 

// Moves/capture for white pawns. No moves captures possible on rank 8.
$this->WPawnAttacks[0] = $bb = new BitBoard(); $bb->num4 = 256;
$this->WPawnAttacks[1] = $bb = new BitBoard(); $bb->num4 = 512; 
$this->WPawnAttacks[2] = $bb = new BitBoard(); $bb->num4 = 1024; 
$this->WPawnAttacks[3] = $bb = new BitBoard(); $bb->num4 = 2048; 
$this->WPawnAttacks[4] = $bb = new BitBoard(); $bb->num4 = 4096;
$this->WPawnAttacks[5] = $bb = new BitBoard(); $bb->num4 = 8192;
$this->WPawnAttacks[6] = $bb = new BitBoard(); $bb->num4 = 16384;
$this->WPawnAttacks[7] = $bb = new BitBoard(); $bb->num4 = 32768;
$this->WPawnAttacks[8] = $bb = new BitBoard(); $bb->num3 = 257; 
$this->WPawnAttacks[9] = $bb = new BitBoard(); $bb->num3 = 514; 
$this->WPawnAttacks[10] = $bb = new BitBoard(); $bb->num3 = 1028; 
$this->WPawnAttacks[11] = $bb = new BitBoard(); $bb->num3 = 2056; 
$this->WPawnAttacks[12] = $bb = new BitBoard(); $bb->num3 = 4112; 
$this->WPawnAttacks[13] = $bb = new BitBoard(); $bb->num3 = 8224; 
$this->WPawnAttacks[14] = $bb = new BitBoard(); $bb->num3 = 16448; 
$this->WPawnAttacks[15] = $bb = new BitBoard(); $bb->num3 = 32896; 
$this->WPawnAttacks[16] = $bb = new BitBoard(); $bb->num3 = 256; 
$this->WPawnAttacks[17] = $bb = new BitBoard(); $bb->num3 = 512; 
$this->WPawnAttacks[18] = $bb = new BitBoard(); $bb->num3 = 1024; 
$this->WPawnAttacks[19] = $bb = new BitBoard(); $bb->num3 = 2048; 
$this->WPawnAttacks[20] = $bb = new BitBoard(); $bb->num3 = 4096; 
$this->WPawnAttacks[21] = $bb = new BitBoard(); $bb->num3 = 8192; 
$this->WPawnAttacks[22] = $bb = new BitBoard(); $bb->num3 = 16384; 
$this->WPawnAttacks[23] = $bb = new BitBoard(); $bb->num3 = 32768; 
$this->WPawnAttacks[24] = $bb = new BitBoard(); $bb->num2 = 1; 
$this->WPawnAttacks[25] = $bb = new BitBoard(); $bb->num2 = 2; 
$this->WPawnAttacks[26] = $bb = new BitBoard(); $bb->num2 = 4; 
$this->WPawnAttacks[27] = $bb = new BitBoard(); $bb->num2 = 8; 
$this->WPawnAttacks[28] = $bb = new BitBoard(); $bb->num2 = 16; 
$this->WPawnAttacks[29] = $bb = new BitBoard(); $bb->num2 = 32; 
$this->WPawnAttacks[30] = $bb = new BitBoard(); $bb->num2 = 64; 
$this->WPawnAttacks[31] = $bb = new BitBoard(); $bb->num2 = 128; 
$this->WPawnAttacks[32] = $bb = new BitBoard(); $bb->num2 = 256; 
$this->WPawnAttacks[33] = $bb = new BitBoard(); $bb->num2 = 512; 
$this->WPawnAttacks[34] = $bb = new BitBoard(); $bb->num2 = 1024; 
$this->WPawnAttacks[35] = $bb = new BitBoard(); $bb->num2 = 2048; 
$this->WPawnAttacks[36] = $bb = new BitBoard(); $bb->num2 = 4096; 
$this->WPawnAttacks[37] = $bb = new BitBoard(); $bb->num2 = 8192; 
$this->WPawnAttacks[38] = $bb = new BitBoard(); $bb->num2 = 16384; 
$this->WPawnAttacks[39] = $bb = new BitBoard(); $bb->num2 = 32768; 
$this->WPawnAttacks[40] = $bb = new BitBoard(); $bb->num1 = 1; 
$this->WPawnAttacks[41] = $bb = new BitBoard(); $bb->num1 = 2; 
$this->WPawnAttacks[42] = $bb = new BitBoard(); $bb->num1 = 4; 
$this->WPawnAttacks[43] = $bb = new BitBoard(); $bb->num1 = 8; 
$this->WPawnAttacks[44] = $bb = new BitBoard(); $bb->num1 = 16; 
$this->WPawnAttacks[45] = $bb = new BitBoard(); $bb->num1 = 32; 
$this->WPawnAttacks[46] = $bb = new BitBoard(); $bb->num1 = 64; 
$this->WPawnAttacks[47] = $bb = new BitBoard(); $bb->num1 = 128; 
$this->WPawnAttacks[48] = $bb = new BitBoard(); $bb->num1 = 256; 
$this->WPawnAttacks[49] = $bb = new BitBoard(); $bb->num1 = 512; 
$this->WPawnAttacks[50] = $bb = new BitBoard(); $bb->num1 = 1024; 
$this->WPawnAttacks[51] = $bb = new BitBoard(); $bb->num1 = 2048; 
$this->WPawnAttacks[52] = $bb = new BitBoard(); $bb->num1 = 4096; 
$this->WPawnAttacks[53] = $bb = new BitBoard(); $bb->num1 = 8192; 
$this->WPawnAttacks[54] = $bb = new BitBoard(); $bb->num1 = 16384; 
$this->WPawnAttacks[55] = $bb = new BitBoard(); $bb->num1 = 32768; 
$this->WPawnAttacks[56] = $bb = new BitBoard(); 
$this->WPawnAttacks[57] = $bb = new BitBoard(); 
$this->WPawnAttacks[58] = $bb = new BitBoard(); 
$this->WPawnAttacks[59] = $bb = new BitBoard(); 
$this->WPawnAttacks[60] = $bb = new BitBoard(); 
$this->WPawnAttacks[61] = $bb = new BitBoard(); 
$this->WPawnAttacks[62] = $bb = new BitBoard(); 
$this->WPawnAttacks[63] = $bb = new BitBoard(); 

$this->WPawnCaptures[0] = $bb = new BitBoard(); $bb->num4 = 512; 
$this->WPawnCaptures[1] = $bb = new BitBoard(); $bb->num4 = 1280;
$this->WPawnCaptures[2] = $bb = new BitBoard(); $bb->num4 = 2560;
$this->WPawnCaptures[3] = $bb = new BitBoard(); $bb->num4 = 5120; 
$this->WPawnCaptures[4] = $bb = new BitBoard(); $bb->num4 = 10240;
$this->WPawnCaptures[5] = $bb = new BitBoard(); $bb->num4 = 20480;
$this->WPawnCaptures[6] = $bb = new BitBoard(); $bb->num4 = 40960;
$this->WPawnCaptures[7] = $bb = new BitBoard(); $bb->num4 = 16384; 
$this->WPawnCaptures[8] = $bb = new BitBoard(); $bb->num3 = 2; 
$this->WPawnCaptures[9] = $bb = new BitBoard(); $bb->num3 = 5; 
$this->WPawnCaptures[10] = $bb = new BitBoard(); $bb->num3 = 10; 
$this->WPawnCaptures[11] = $bb = new BitBoard(); $bb->num3 = 20; 
$this->WPawnCaptures[12] = $bb = new BitBoard(); $bb->num3 = 40; 
$this->WPawnCaptures[13] = $bb = new BitBoard(); $bb->num3 = 80; 
$this->WPawnCaptures[14] = $bb = new BitBoard(); $bb->num3 = 160; 
$this->WPawnCaptures[15] = $bb = new BitBoard(); $bb->num3 = 64; 
$this->WPawnCaptures[16] = $bb = new BitBoard(); $bb->num3 = 512; 
$this->WPawnCaptures[17] = $bb = new BitBoard(); $bb->num3 = 1280; 
$this->WPawnCaptures[18] = $bb = new BitBoard(); $bb->num3 = 2560; 
$this->WPawnCaptures[19] = $bb = new BitBoard(); $bb->num3 = 5120; 
$this->WPawnCaptures[20] = $bb = new BitBoard(); $bb->num3 = 10240; 
$this->WPawnCaptures[21] = $bb = new BitBoard(); $bb->num3 = 20480; 
$this->WPawnCaptures[22] = $bb = new BitBoard(); $bb->num3 = 40960; 
$this->WPawnCaptures[23] = $bb = new BitBoard(); $bb->num3 = 16384; 
$this->WPawnCaptures[24] = $bb = new BitBoard(); $bb->num2 = 2; 
$this->WPawnCaptures[25] = $bb = new BitBoard(); $bb->num2 = 5; 
$this->WPawnCaptures[26] = $bb = new BitBoard(); $bb->num2 = 10; 
$this->WPawnCaptures[27] = $bb = new BitBoard(); $bb->num2 = 20; 
$this->WPawnCaptures[28] = $bb = new BitBoard(); $bb->num2 = 40; 
$this->WPawnCaptures[29] = $bb = new BitBoard(); $bb->num2 = 80; 
$this->WPawnCaptures[30] = $bb = new BitBoard(); $bb->num2 = 160; 
$this->WPawnCaptures[31] = $bb = new BitBoard(); $bb->num2 = 64; 
$this->WPawnCaptures[32] = $bb = new BitBoard(); $bb->num2 = 512; 
$this->WPawnCaptures[33] = $bb = new BitBoard(); $bb->num2 = 1280; 
$this->WPawnCaptures[34] = $bb = new BitBoard(); $bb->num2 = 2560; 
$this->WPawnCaptures[35] = $bb = new BitBoard(); $bb->num2 = 5120; 
$this->WPawnCaptures[36] = $bb = new BitBoard(); $bb->num2 = 10240; 
$this->WPawnCaptures[37] = $bb = new BitBoard(); $bb->num2 = 20480; 
$this->WPawnCaptures[38] = $bb = new BitBoard(); $bb->num2 = 40960; 
$this->WPawnCaptures[39] = $bb = new BitBoard(); $bb->num2 = 16384; 
$this->WPawnCaptures[40] = $bb = new BitBoard(); $bb->num1 = 2; 
$this->WPawnCaptures[41] = $bb = new BitBoard(); $bb->num1 = 5; 
$this->WPawnCaptures[42] = $bb = new BitBoard(); $bb->num1 = 10; 
$this->WPawnCaptures[43] = $bb = new BitBoard(); $bb->num1 = 20; 
$this->WPawnCaptures[44] = $bb = new BitBoard(); $bb->num1 = 40; 
$this->WPawnCaptures[45] = $bb = new BitBoard(); $bb->num1 = 80; 
$this->WPawnCaptures[46] = $bb = new BitBoard(); $bb->num1 = 160; 
$this->WPawnCaptures[47] = $bb = new BitBoard(); $bb->num1 = 64; 
$this->WPawnCaptures[48] = $bb = new BitBoard(); $bb->num1 = 512; 
$this->WPawnCaptures[49] = $bb = new BitBoard(); $bb->num1 = 1280; 
$this->WPawnCaptures[50] = $bb = new BitBoard(); $bb->num1 = 2560; 
$this->WPawnCaptures[51] = $bb = new BitBoard(); $bb->num1 = 5120; 
$this->WPawnCaptures[52] = $bb = new BitBoard(); $bb->num1 = 10240; 
$this->WPawnCaptures[53] = $bb = new BitBoard(); $bb->num1 = 20480; 
$this->WPawnCaptures[54] = $bb = new BitBoard(); $bb->num1 = 40960; 
$this->WPawnCaptures[55] = $bb = new BitBoard(); $bb->num1 = 16384; 
$this->WPawnCaptures[56] = $bb = new BitBoard(); 
$this->WPawnCaptures[57] = $bb = new BitBoard(); 
$this->WPawnCaptures[58] = $bb = new BitBoard(); 
$this->WPawnCaptures[59] = $bb = new BitBoard(); 
$this->WPawnCaptures[60] = $bb = new BitBoard(); 
$this->WPawnCaptures[61] = $bb = new BitBoard(); 
$this->WPawnCaptures[62] = $bb = new BitBoard(); 
$this->WPawnCaptures[63] = $bb = new BitBoard(); 

// Moves/captures for black pawns. No moves/captures possible on rank 1
$this->BPawnAttacks[0] = $bb = new BitBoard(); 
$this->BPawnAttacks[1] = $bb = new BitBoard(); 
$this->BPawnAttacks[2] = $bb = new BitBoard(); 
$this->BPawnAttacks[3] = $bb = new BitBoard(); 
$this->BPawnAttacks[4] = $bb = new BitBoard(); 
$this->BPawnAttacks[5] = $bb = new BitBoard(); 
$this->BPawnAttacks[6] = $bb = new BitBoard(); 
$this->BPawnAttacks[7] = $bb = new BitBoard(); 
$this->BPawnAttacks[8] = $bb = new BitBoard(); $bb->num4 = 1;
$this->BPawnAttacks[9] = $bb = new BitBoard(); $bb->num4 = 2;
$this->BPawnAttacks[10] = $bb = new BitBoard(); $bb->num4 = 4;
$this->BPawnAttacks[11] = $bb = new BitBoard(); $bb->num4 = 8;
$this->BPawnAttacks[12] = $bb = new BitBoard(); $bb->num4 = 16;
$this->BPawnAttacks[13] = $bb = new BitBoard(); $bb->num4 = 32;
$this->BPawnAttacks[14] = $bb = new BitBoard(); $bb->num4 = 64;
$this->BPawnAttacks[15] = $bb = new BitBoard(); $bb->num4 = 128;
$this->BPawnAttacks[16] = $bb = new BitBoard(); $bb->num4 = 256;
$this->BPawnAttacks[17] = $bb = new BitBoard(); $bb->num4 = 512;
$this->BPawnAttacks[18] = $bb = new BitBoard(); $bb->num4 = 1024;
$this->BPawnAttacks[19] = $bb = new BitBoard(); $bb->num4 = 2048;
$this->BPawnAttacks[20] = $bb = new BitBoard(); $bb->num4 = 4096;
$this->BPawnAttacks[21] = $bb = new BitBoard(); $bb->num4 = 8192;
$this->BPawnAttacks[22] = $bb = new BitBoard(); $bb->num4 = 16384;
$this->BPawnAttacks[23] = $bb = new BitBoard(); $bb->num4 = 32768;
$this->BPawnAttacks[24] = $bb = new BitBoard(); $bb->num3 = 1; 
$this->BPawnAttacks[25] = $bb = new BitBoard(); $bb->num3 = 2; 
$this->BPawnAttacks[26] = $bb = new BitBoard(); $bb->num3 = 4; 
$this->BPawnAttacks[27] = $bb = new BitBoard(); $bb->num3 = 8; 
$this->BPawnAttacks[28] = $bb = new BitBoard(); $bb->num3 = 16; 
$this->BPawnAttacks[29] = $bb = new BitBoard(); $bb->num3 = 32; 
$this->BPawnAttacks[30] = $bb = new BitBoard(); $bb->num3 = 64; 
$this->BPawnAttacks[31] = $bb = new BitBoard(); $bb->num3 = 128; 
$this->BPawnAttacks[32] = $bb = new BitBoard(); $bb->num3 = 256; 
$this->BPawnAttacks[33] = $bb = new BitBoard(); $bb->num3 = 512; 
$this->BPawnAttacks[34] = $bb = new BitBoard(); $bb->num3 = 1024; 
$this->BPawnAttacks[35] = $bb = new BitBoard(); $bb->num3 = 2048; 
$this->BPawnAttacks[36] = $bb = new BitBoard(); $bb->num3 = 4096; 
$this->BPawnAttacks[37] = $bb = new BitBoard(); $bb->num3 = 8192; 
$this->BPawnAttacks[38] = $bb = new BitBoard(); $bb->num3 = 16384; 
$this->BPawnAttacks[39] = $bb = new BitBoard(); $bb->num3 = 32768; 
$this->BPawnAttacks[40] = $bb = new BitBoard(); $bb->num2 = 1; 
$this->BPawnAttacks[41] = $bb = new BitBoard(); $bb->num2 = 2; 
$this->BPawnAttacks[42] = $bb = new BitBoard(); $bb->num2 = 4; 
$this->BPawnAttacks[43] = $bb = new BitBoard(); $bb->num2 = 8; 
$this->BPawnAttacks[44] = $bb = new BitBoard(); $bb->num2 = 16; 
$this->BPawnAttacks[45] = $bb = new BitBoard(); $bb->num2 = 32; 
$this->BPawnAttacks[46] = $bb = new BitBoard(); $bb->num2 = 64; 
$this->BPawnAttacks[47] = $bb = new BitBoard(); $bb->num2 = 128; 
$this->BPawnAttacks[48] = $bb = new BitBoard(); $bb->num2 = 257; 
$this->BPawnAttacks[49] = $bb = new BitBoard(); $bb->num2 = 514; 
$this->BPawnAttacks[50] = $bb = new BitBoard(); $bb->num2 = 1028; 
$this->BPawnAttacks[51] = $bb = new BitBoard(); $bb->num2 = 2056; 
$this->BPawnAttacks[52] = $bb = new BitBoard(); $bb->num2 = 4112; 
$this->BPawnAttacks[53] = $bb = new BitBoard(); $bb->num2 = 8224; 
$this->BPawnAttacks[54] = $bb = new BitBoard(); $bb->num2 = 16448; 
$this->BPawnAttacks[55] = $bb = new BitBoard(); $bb->num2 = 32896; 
$this->BPawnAttacks[56] = $bb = new BitBoard(); $bb->num1 = 1;
$this->BPawnAttacks[57] = $bb = new BitBoard(); $bb->num1 = 2;
$this->BPawnAttacks[58] = $bb = new BitBoard(); $bb->num1 = 4;
$this->BPawnAttacks[59] = $bb = new BitBoard(); $bb->num1 = 8;
$this->BPawnAttacks[60] = $bb = new BitBoard(); $bb->num1 = 16;
$this->BPawnAttacks[61] = $bb = new BitBoard(); $bb->num1 = 32;
$this->BPawnAttacks[62] = $bb = new BitBoard(); $bb->num1 = 64;
$this->BPawnAttacks[63] = $bb = new BitBoard(); $bb->num1 = 128;

$this->BPawnCaptures[0] = $bb = new BitBoard(); 
$this->BPawnCaptures[1] = $bb = new BitBoard(); 
$this->BPawnCaptures[2] = $bb = new BitBoard(); 
$this->BPawnCaptures[3] = $bb = new BitBoard(); 
$this->BPawnCaptures[4] = $bb = new BitBoard(); 
$this->BPawnCaptures[5] = $bb = new BitBoard(); 
$this->BPawnCaptures[6] = $bb = new BitBoard(); 
$this->BPawnCaptures[7] = $bb = new BitBoard(); 
$this->BPawnCaptures[8] = $bb = new BitBoard(); $bb->num4 = 2;
$this->BPawnCaptures[9] = $bb = new BitBoard(); $bb->num4 = 5;
$this->BPawnCaptures[10] = $bb = new BitBoard(); $bb->num4 = 10;
$this->BPawnCaptures[11] = $bb = new BitBoard(); $bb->num4 = 20;
$this->BPawnCaptures[12] = $bb = new BitBoard(); $bb->num4 = 40;
$this->BPawnCaptures[13] = $bb = new BitBoard(); $bb->num4 = 80;
$this->BPawnCaptures[14] = $bb = new BitBoard(); $bb->num4 = 160;
$this->BPawnCaptures[15] = $bb = new BitBoard(); $bb->num4 = 64;
$this->BPawnCaptures[16] = $bb = new BitBoard(); $bb->num4 = 512;
$this->BPawnCaptures[17] = $bb = new BitBoard(); $bb->num4 = 1280;
$this->BPawnCaptures[18] = $bb = new BitBoard(); $bb->num4 = 2560;
$this->BPawnCaptures[19] = $bb = new BitBoard(); $bb->num4 = 5120;
$this->BPawnCaptures[20] = $bb = new BitBoard(); $bb->num4 = 10240;
$this->BPawnCaptures[21] = $bb = new BitBoard(); $bb->num4 = 20480;
$this->BPawnCaptures[22] = $bb = new BitBoard(); $bb->num4 = 40960;
$this->BPawnCaptures[23] = $bb = new BitBoard(); $bb->num4 = 16384;
$this->BPawnCaptures[24] = $bb = new BitBoard(); $bb->num3 = 2; 
$this->BPawnCaptures[25] = $bb = new BitBoard(); $bb->num3 = 5; 
$this->BPawnCaptures[26] = $bb = new BitBoard(); $bb->num3 = 10; 
$this->BPawnCaptures[27] = $bb = new BitBoard(); $bb->num3 = 20; 
$this->BPawnCaptures[28] = $bb = new BitBoard(); $bb->num3 = 40; 
$this->BPawnCaptures[29] = $bb = new BitBoard(); $bb->num3 = 80; 
$this->BPawnCaptures[30] = $bb = new BitBoard(); $bb->num3 = 160; 
$this->BPawnCaptures[31] = $bb = new BitBoard(); $bb->num3 = 64; 
$this->BPawnCaptures[32] = $bb = new BitBoard(); $bb->num3 = 512; 
$this->BPawnCaptures[33] = $bb = new BitBoard(); $bb->num3 = 1280; 
$this->BPawnCaptures[34] = $bb = new BitBoard(); $bb->num3 = 2560; 
$this->BPawnCaptures[35] = $bb = new BitBoard(); $bb->num3 = 5120; 
$this->BPawnCaptures[36] = $bb = new BitBoard(); $bb->num3 = 10240; 
$this->BPawnCaptures[37] = $bb = new BitBoard(); $bb->num3 = 20480; 
$this->BPawnCaptures[38] = $bb = new BitBoard(); $bb->num3 = 40960; 
$this->BPawnCaptures[39] = $bb = new BitBoard(); $bb->num3 = 16384; 
$this->BPawnCaptures[40] = $bb = new BitBoard(); $bb->num2 = 2; 
$this->BPawnCaptures[41] = $bb = new BitBoard(); $bb->num2 = 5; 
$this->BPawnCaptures[42] = $bb = new BitBoard(); $bb->num2 = 10; 
$this->BPawnCaptures[43] = $bb = new BitBoard(); $bb->num2 = 20; 
$this->BPawnCaptures[44] = $bb = new BitBoard(); $bb->num2 = 40; 
$this->BPawnCaptures[45] = $bb = new BitBoard(); $bb->num2 = 80; 
$this->BPawnCaptures[46] = $bb = new BitBoard(); $bb->num2 = 160; 
$this->BPawnCaptures[47] = $bb = new BitBoard(); $bb->num2 = 64; 
$this->BPawnCaptures[48] = $bb = new BitBoard(); $bb->num2 = 512; 
$this->BPawnCaptures[49] = $bb = new BitBoard(); $bb->num2 = 1280; 
$this->BPawnCaptures[50] = $bb = new BitBoard(); $bb->num2 = 2560; 
$this->BPawnCaptures[51] = $bb = new BitBoard(); $bb->num2 = 5120; 
$this->BPawnCaptures[52] = $bb = new BitBoard(); $bb->num2 = 10240; 
$this->BPawnCaptures[53] = $bb = new BitBoard(); $bb->num2 = 20480; 
$this->BPawnCaptures[54] = $bb = new BitBoard(); $bb->num2 = 40960; 
$this->BPawnCaptures[55] = $bb = new BitBoard(); $bb->num2 = 16384; 
$this->BPawnCaptures[56] = $bb = new BitBoard(); $bb->num1 = 2;
$this->BPawnCaptures[57] = $bb = new BitBoard(); $bb->num1 = 5;
$this->BPawnCaptures[58] = $bb = new BitBoard(); $bb->num1 = 10; 
$this->BPawnCaptures[59] = $bb = new BitBoard(); $bb->num1 = 20;
$this->BPawnCaptures[60] = $bb = new BitBoard(); $bb->num1 = 40;
$this->BPawnCaptures[61] = $bb = new BitBoard(); $bb->num1 = 80;  
$this->BPawnCaptures[62] = $bb = new BitBoard(); $bb->num1 = 160;
$this->BPawnCaptures[63] = $bb = new BitBoard(); $bb->num1 = 64; 

// Moves in straight lines (vertical, horizontal and diagonal)
$this->Plus1[0] = $bb = new BitBoard(); $bb->num4 = 254;
$this->Plus1[1] = $bb = new BitBoard(); $bb->num4 = 252;
$this->Plus1[2] = $bb = new BitBoard(); $bb->num4 = 248;
$this->Plus1[3] = $bb = new BitBoard(); $bb->num4 = 240;
$this->Plus1[4] = $bb = new BitBoard(); $bb->num4 = 224;
$this->Plus1[5] = $bb = new BitBoard(); $bb->num4 = 192;
$this->Plus1[6] = $bb = new BitBoard(); $bb->num4 = 128;
$this->Plus1[7] = $bb = new BitBoard(); 
$this->Plus1[8] = $bb = new BitBoard(); $bb->num4 = 65024;
$this->Plus1[9] = $bb = new BitBoard(); $bb->num4 = 64512;
$this->Plus1[10] = $bb = new BitBoard(); $bb->num4 = 63488;
$this->Plus1[11] = $bb = new BitBoard(); $bb->num4 = 61440;
$this->Plus1[12] = $bb = new BitBoard(); $bb->num4 = 57344;
$this->Plus1[13] = $bb = new BitBoard(); $bb->num4 = 49152;
$this->Plus1[14] = $bb = new BitBoard(); $bb->num4 = 32768;
$this->Plus1[15] = $bb = new BitBoard(); 
$this->Plus1[16] = $bb = new BitBoard(); $bb->num3 = 254; 
$this->Plus1[17] = $bb = new BitBoard(); $bb->num3 = 252; 
$this->Plus1[18] = $bb = new BitBoard(); $bb->num3 = 248; 
$this->Plus1[19] = $bb = new BitBoard(); $bb->num3 = 240; 
$this->Plus1[20] = $bb = new BitBoard(); $bb->num3 = 224; 
$this->Plus1[21] = $bb = new BitBoard(); $bb->num3 = 192; 
$this->Plus1[22] = $bb = new BitBoard(); $bb->num3 = 128; 
$this->Plus1[23] = $bb = new BitBoard(); 
$this->Plus1[24] = $bb = new BitBoard(); $bb->num3 = 65024; 
$this->Plus1[25] = $bb = new BitBoard(); $bb->num3 = 64512; 
$this->Plus1[26] = $bb = new BitBoard(); $bb->num3 = 63488; 
$this->Plus1[27] = $bb = new BitBoard(); $bb->num3 = 61440; 
$this->Plus1[28] = $bb = new BitBoard(); $bb->num3 = 57344; 
$this->Plus1[29] = $bb = new BitBoard(); $bb->num3 = 49152; 
$this->Plus1[30] = $bb = new BitBoard(); $bb->num3 = 32768; 
$this->Plus1[31] = $bb = new BitBoard(); 
$this->Plus1[32] = $bb = new BitBoard(); $bb->num2 = 254; 
$this->Plus1[33] = $bb = new BitBoard(); $bb->num2 = 252; 
$this->Plus1[34] = $bb = new BitBoard(); $bb->num2 = 248; 
$this->Plus1[35] = $bb = new BitBoard(); $bb->num2 = 240; 
$this->Plus1[36] = $bb = new BitBoard(); $bb->num2 = 224; 
$this->Plus1[37] = $bb = new BitBoard(); $bb->num2 = 192; 
$this->Plus1[38] = $bb = new BitBoard(); $bb->num2 = 128; 
$this->Plus1[39] = $bb = new BitBoard(); 
$this->Plus1[40] = $bb = new BitBoard(); $bb->num2 = 65024; 
$this->Plus1[41] = $bb = new BitBoard(); $bb->num2 = 64512; 
$this->Plus1[42] = $bb = new BitBoard(); $bb->num2 = 63488; 
$this->Plus1[43] = $bb = new BitBoard(); $bb->num2 = 61440; 
$this->Plus1[44] = $bb = new BitBoard(); $bb->num2 = 57344; 
$this->Plus1[45] = $bb = new BitBoard(); $bb->num2 = 49152; 
$this->Plus1[46] = $bb = new BitBoard(); $bb->num2 = 32768; 
$this->Plus1[47] = $bb = new BitBoard(); 
$this->Plus1[48] = $bb = new BitBoard(); $bb->num1 = 254; 
$this->Plus1[49] = $bb = new BitBoard(); $bb->num1 = 252; 
$this->Plus1[50] = $bb = new BitBoard(); $bb->num1 = 248; 
$this->Plus1[51] = $bb = new BitBoard(); $bb->num1 = 240; 
$this->Plus1[52] = $bb = new BitBoard(); $bb->num1 = 224; 
$this->Plus1[53] = $bb = new BitBoard(); $bb->num1 = 192; 
$this->Plus1[54] = $bb = new BitBoard(); $bb->num1 = 128; 
$this->Plus1[55] = $bb = new BitBoard(); 
$this->Plus1[56] = $bb = new BitBoard(); $bb->num1 = 65024; 
$this->Plus1[57] = $bb = new BitBoard(); $bb->num1 = 64512; 
$this->Plus1[58] = $bb = new BitBoard(); $bb->num1 = 63488; 
$this->Plus1[59] = $bb = new BitBoard(); $bb->num1 = 61440; 
$this->Plus1[60] = $bb = new BitBoard(); $bb->num1 = 57344; 
$this->Plus1[61] = $bb = new BitBoard(); $bb->num1 = 49152; 
$this->Plus1[62] = $bb = new BitBoard(); $bb->num1 = 32768; 
$this->Plus1[63] = $bb = new BitBoard(); 

$this->Plus7[0] = $bb = new BitBoard(); 
$this->Plus7[1] = $bb = new BitBoard(); $bb->num4 = 256;
$this->Plus7[2] = $bb = new BitBoard(); $bb->num3 = 1; $bb->num4 = 512;
$this->Plus7[3] = $bb = new BitBoard(); $bb->num3 = 258; $bb->num4 = 1024;
$this->Plus7[4] = $bb = new BitBoard(); $bb->num2 = 1; $bb->num3 = 516; $bb->num4 = 2048;
$this->Plus7[5] = $bb = new BitBoard(); $bb->num2 = 258; $bb->num3 = 1032; $bb->num4 = 4096;
$this->Plus7[6] = $bb = new BitBoard(); $bb->num1 = 1; $bb->num2 = 516; $bb->num3 = 2064; $bb->num4 = 8192;
$this->Plus7[7] = $bb = new BitBoard(); $bb->num1 = 258; $bb->num2 = 1032; $bb->num3 = 4128; $bb->num4 = 16384;
$this->Plus7[8] = $bb = new BitBoard(); 
$this->Plus7[9] = $bb = new BitBoard(); $bb->num3 = 1; 
$this->Plus7[10] = $bb = new BitBoard(); $bb->num3 = 258; 
$this->Plus7[11] = $bb = new BitBoard(); $bb->num2 = 1; $bb->num3 = 516; 
$this->Plus7[12] = $bb = new BitBoard(); $bb->num2 = 258; $bb->num3 = 1032; 
$this->Plus7[13] = $bb = new BitBoard(); $bb->num1 = 1; $bb->num2 = 516; $bb->num3 = 2064; 
$this->Plus7[14] = $bb = new BitBoard(); $bb->num1 = 258; $bb->num2 = 1032; $bb->num3 = 4128; 
$this->Plus7[15] = $bb = new BitBoard(); $bb->num1 = 516; $bb->num2 = 2064; $bb->num3 = 8256; 
$this->Plus7[16] = $bb = new BitBoard(); 
$this->Plus7[17] = $bb = new BitBoard(); $bb->num3 = 256; 
$this->Plus7[18] = $bb = new BitBoard(); $bb->num2 = 1; $bb->num3 = 512; 
$this->Plus7[19] = $bb = new BitBoard(); $bb->num2 = 258; $bb->num3 = 1024; 
$this->Plus7[20] = $bb = new BitBoard(); $bb->num1 = 1; $bb->num2 = 516; $bb->num3 = 2048; 
$this->Plus7[21] = $bb = new BitBoard(); $bb->num1 = 258; $bb->num2 = 1032; $bb->num3 = 4096; 
$this->Plus7[22] = $bb = new BitBoard(); $bb->num1 = 516; $bb->num2 = 2064; $bb->num3 = 8192; 
$this->Plus7[23] = $bb = new BitBoard(); $bb->num1 = 1032; $bb->num2 = 4128; $bb->num3 = 16384; 
$this->Plus7[24] = $bb = new BitBoard(); 
$this->Plus7[25] = $bb = new BitBoard(); $bb->num2 = 1; 
$this->Plus7[26] = $bb = new BitBoard(); $bb->num2 = 258; 
$this->Plus7[27] = $bb = new BitBoard(); $bb->num1 = 1; $bb->num2 = 516; 
$this->Plus7[28] = $bb = new BitBoard(); $bb->num1 = 258; $bb->num2 = 1032; 
$this->Plus7[29] = $bb = new BitBoard(); $bb->num1 = 516; $bb->num2 = 2064; 
$this->Plus7[30] = $bb = new BitBoard(); $bb->num1 = 1032; $bb->num2 = 4128; 
$this->Plus7[31] = $bb = new BitBoard(); $bb->num1 = 2064; $bb->num2 = 8256; 
$this->Plus7[32] = $bb = new BitBoard(); 
$this->Plus7[33] = $bb = new BitBoard(); $bb->num2 = 256; 
$this->Plus7[34] = $bb = new BitBoard(); $bb->num1 = 1; $bb->num2 = 512; 
$this->Plus7[35] = $bb = new BitBoard(); $bb->num1 = 258; $bb->num2 = 1024; 
$this->Plus7[36] = $bb = new BitBoard(); $bb->num1 = 516; $bb->num2 = 2048; 
$this->Plus7[37] = $bb = new BitBoard(); $bb->num1 = 1032; $bb->num2 = 4096; 
$this->Plus7[38] = $bb = new BitBoard(); $bb->num1 = 2064; $bb->num2 = 8192; 
$this->Plus7[39] = $bb = new BitBoard(); $bb->num1 = 4128; $bb->num2 = 16384; 
$this->Plus7[40] = $bb = new BitBoard(); 
$this->Plus7[41] = $bb = new BitBoard(); $bb->num1 = 1; 
$this->Plus7[42] = $bb = new BitBoard(); $bb->num1 = 258; 
$this->Plus7[43] = $bb = new BitBoard(); $bb->num1 = 516; 
$this->Plus7[44] = $bb = new BitBoard(); $bb->num1 = 1032; 
$this->Plus7[45] = $bb = new BitBoard(); $bb->num1 = 2064; 
$this->Plus7[46] = $bb = new BitBoard(); $bb->num1 = 4128; 
$this->Plus7[47] = $bb = new BitBoard(); $bb->num1 = 8256; 
$this->Plus7[48] = $bb = new BitBoard(); 
$this->Plus7[49] = $bb = new BitBoard(); $bb->num1 = 256; 
$this->Plus7[50] = $bb = new BitBoard(); $bb->num1 = 512; 
$this->Plus7[51] = $bb = new BitBoard(); $bb->num1 = 1024; 
$this->Plus7[52] = $bb = new BitBoard(); $bb->num1 = 2048; 
$this->Plus7[53] = $bb = new BitBoard(); $bb->num1 = 4096; 
$this->Plus7[54] = $bb = new BitBoard(); $bb->num1 = 8192; 
$this->Plus7[55] = $bb = new BitBoard(); $bb->num1 = 16384; 
$this->Plus7[56] = $bb = new BitBoard(); 
$this->Plus7[57] = $bb = new BitBoard(); 
$this->Plus7[58] = $bb = new BitBoard(); 
$this->Plus7[59] = $bb = new BitBoard(); 
$this->Plus7[60] = $bb = new BitBoard(); 
$this->Plus7[61] = $bb = new BitBoard(); 
$this->Plus7[62] = $bb = new BitBoard(); 
$this->Plus7[63] = $bb = new BitBoard(); 

$this->Plus8[0] = $bb = new BitBoard(); $bb->num1 = 257; $bb->num2 = 257; $bb->num3 = 257; $bb->num4 = 256;
$this->Plus8[1] = $bb = new BitBoard(); $bb->num1 = 514; $bb->num2 = 514; $bb->num3 = 514; $bb->num4 = 512;
$this->Plus8[2] = $bb = new BitBoard(); $bb->num1 = 1028; $bb->num2 = 1028; $bb->num3 = 1028; $bb->num4 = 1024;
$this->Plus8[3] = $bb = new BitBoard(); $bb->num1 = 2056; $bb->num2 = 2056; $bb->num3 = 2056; $bb->num4 = 2048;
$this->Plus8[4] = $bb = new BitBoard(); $bb->num1 = 4112; $bb->num2 = 4112; $bb->num3 = 4112; $bb->num4 = 4096;
$this->Plus8[5] = $bb = new BitBoard(); $bb->num1 = 8224; $bb->num2 = 8224; $bb->num3 = 8224; $bb->num4 = 8192;
$this->Plus8[6] = $bb = new BitBoard(); $bb->num1 = 16448; $bb->num2 = 16448; $bb->num3 = 16448; $bb->num4 = 16384;
$this->Plus8[7] = $bb = new BitBoard(); $bb->num1 = 32896; $bb->num2 = 32896; $bb->num3 = 32896; $bb->num4 = 32768;
$this->Plus8[8] = $bb = new BitBoard(); $bb->num1 = 257; $bb->num2 = 257; $bb->num3 = 257; 
$this->Plus8[9] = $bb = new BitBoard(); $bb->num1 = 514; $bb->num2 = 514; $bb->num3 = 514; 
$this->Plus8[10] = $bb = new BitBoard(); $bb->num1 = 1028; $bb->num2 = 1028; $bb->num3 = 1028; 
$this->Plus8[11] = $bb = new BitBoard(); $bb->num1 = 2056; $bb->num2 = 2056; $bb->num3 = 2056; 
$this->Plus8[12] = $bb = new BitBoard(); $bb->num1 = 4112; $bb->num2 = 4112; $bb->num3 = 4112; 
$this->Plus8[13] = $bb = new BitBoard(); $bb->num1 = 8224; $bb->num2 = 8224; $bb->num3 = 8224; 
$this->Plus8[14] = $bb = new BitBoard(); $bb->num1 = 16448; $bb->num2 = 16448; $bb->num3 = 16448; 
$this->Plus8[15] = $bb = new BitBoard(); $bb->num1 = 32896; $bb->num2 = 32896; $bb->num3 = 32896; 
$this->Plus8[16] = $bb = new BitBoard(); $bb->num1 = 257; $bb->num2 = 257; $bb->num3 = 256; 
$this->Plus8[17] = $bb = new BitBoard(); $bb->num1 = 514; $bb->num2 = 514; $bb->num3 = 512; 
$this->Plus8[18] = $bb = new BitBoard(); $bb->num1 = 1028; $bb->num2 = 1028; $bb->num3 = 1024; 
$this->Plus8[19] = $bb = new BitBoard(); $bb->num1 = 2056; $bb->num2 = 2056; $bb->num3 = 2048; 
$this->Plus8[20] = $bb = new BitBoard(); $bb->num1 = 4112; $bb->num2 = 4112; $bb->num3 = 4096; 
$this->Plus8[21] = $bb = new BitBoard(); $bb->num1 = 8224; $bb->num2 = 8224; $bb->num3 = 8192; 
$this->Plus8[22] = $bb = new BitBoard(); $bb->num1 = 16448; $bb->num2 = 16448; $bb->num3 = 16384; 
$this->Plus8[23] = $bb = new BitBoard(); $bb->num1 = 32896; $bb->num2 = 32896; $bb->num3 = 32768; 
$this->Plus8[24] = $bb = new BitBoard(); $bb->num1 = 257; $bb->num2 = 257; 
$this->Plus8[25] = $bb = new BitBoard(); $bb->num1 = 514; $bb->num2 = 514; 
$this->Plus8[26] = $bb = new BitBoard(); $bb->num1 = 1028; $bb->num2 = 1028; 
$this->Plus8[27] = $bb = new BitBoard(); $bb->num1 = 2056; $bb->num2 = 2056; 
$this->Plus8[28] = $bb = new BitBoard(); $bb->num1 = 4112; $bb->num2 = 4112; 
$this->Plus8[29] = $bb = new BitBoard(); $bb->num1 = 8224; $bb->num2 = 8224; 
$this->Plus8[30] = $bb = new BitBoard(); $bb->num1 = 16448; $bb->num2 = 16448; 
$this->Plus8[31] = $bb = new BitBoard(); $bb->num1 = 32896; $bb->num2 = 32896; 
$this->Plus8[32] = $bb = new BitBoard(); $bb->num1 = 257; $bb->num2 = 256; 
$this->Plus8[33] = $bb = new BitBoard(); $bb->num1 = 514; $bb->num2 = 512; 
$this->Plus8[34] = $bb = new BitBoard(); $bb->num1 = 1028; $bb->num2 = 1024; 
$this->Plus8[35] = $bb = new BitBoard(); $bb->num1 = 2056; $bb->num2 = 2048; 
$this->Plus8[36] = $bb = new BitBoard(); $bb->num1 = 4112; $bb->num2 = 4096; 
$this->Plus8[37] = $bb = new BitBoard(); $bb->num1 = 8224; $bb->num2 = 8192; 
$this->Plus8[38] = $bb = new BitBoard(); $bb->num1 = 16448; $bb->num2 = 16384; 
$this->Plus8[39] = $bb = new BitBoard(); $bb->num1 = 32896; $bb->num2 = 32768; 
$this->Plus8[40] = $bb = new BitBoard(); $bb->num1 = 257; 
$this->Plus8[41] = $bb = new BitBoard(); $bb->num1 = 514; 
$this->Plus8[42] = $bb = new BitBoard(); $bb->num1 = 1028; 
$this->Plus8[43] = $bb = new BitBoard(); $bb->num1 = 2056; 
$this->Plus8[44] = $bb = new BitBoard(); $bb->num1 = 4112; 
$this->Plus8[45] = $bb = new BitBoard(); $bb->num1 = 8224; 
$this->Plus8[46] = $bb = new BitBoard(); $bb->num1 = 16448; 
$this->Plus8[47] = $bb = new BitBoard(); $bb->num1 = 32896; 
$this->Plus8[48] = $bb = new BitBoard(); $bb->num1 = 256; 
$this->Plus8[49] = $bb = new BitBoard(); $bb->num1 = 512; 
$this->Plus8[50] = $bb = new BitBoard(); $bb->num1 = 1024; 
$this->Plus8[51] = $bb = new BitBoard(); $bb->num1 = 2048; 
$this->Plus8[52] = $bb = new BitBoard(); $bb->num1 = 4096; 
$this->Plus8[53] = $bb = new BitBoard(); $bb->num1 = 8192; 
$this->Plus8[54] = $bb = new BitBoard(); $bb->num1 = 16384; 
$this->Plus8[55] = $bb = new BitBoard(); $bb->num1 = 32768; 
$this->Plus8[56] = $bb = new BitBoard(); 
$this->Plus8[57] = $bb = new BitBoard(); 
$this->Plus8[58] = $bb = new BitBoard(); 
$this->Plus8[59] = $bb = new BitBoard(); 
$this->Plus8[60] = $bb = new BitBoard(); 
$this->Plus8[61] = $bb = new BitBoard(); 
$this->Plus8[62] = $bb = new BitBoard(); 
$this->Plus8[63] = $bb = new BitBoard(); 

$this->Plus9[0] = $bb = new BitBoard(); $bb->num1 = 32832; $bb->num2 = 8208; $bb->num3 = 2052; $bb->num4 = 512;
$this->Plus9[1] = $bb = new BitBoard(); $bb->num1 = 128; $bb->num2 = 16416; $bb->num3 = 4104; $bb->num4 = 1024;
$this->Plus9[2] = $bb = new BitBoard(); $bb->num2 = 32832; $bb->num3 = 8208; $bb->num4 = 2048;
$this->Plus9[3] = $bb = new BitBoard(); $bb->num2 = 128; $bb->num3 = 16416; $bb->num4 = 4096;
$this->Plus9[4] = $bb = new BitBoard(); $bb->num3 = 32832; $bb->num4 = 8192;
$this->Plus9[5] = $bb = new BitBoard(); $bb->num3 = 128; $bb->num4 = 16384;
$this->Plus9[6] = $bb = new BitBoard(); $bb->num4 = 32768;
$this->Plus9[7] = $bb = new BitBoard(); 
$this->Plus9[8] = $bb = new BitBoard(); $bb->num1 = 16416; $bb->num2 = 4104; $bb->num3 = 1026; 
$this->Plus9[9] = $bb = new BitBoard(); $bb->num1 = 32832; $bb->num2 = 8208; $bb->num3 = 2052; 
$this->Plus9[10] = $bb = new BitBoard(); $bb->num1 = 128; $bb->num2 = 16416; $bb->num3 = 4104; 
$this->Plus9[11] = $bb = new BitBoard(); $bb->num2 = 32832; $bb->num3 = 8208; 
$this->Plus9[12] = $bb = new BitBoard(); $bb->num2 = 128; $bb->num3 = 16416; 
$this->Plus9[13] = $bb = new BitBoard(); $bb->num3 = 32832; 
$this->Plus9[14] = $bb = new BitBoard(); $bb->num3 = 128; 
$this->Plus9[15] = $bb = new BitBoard(); 
$this->Plus9[16] = $bb = new BitBoard(); $bb->num1 = 8208; $bb->num2 = 2052; $bb->num3 = 512; 
$this->Plus9[17] = $bb = new BitBoard(); $bb->num1 = 16416; $bb->num2 = 4104; $bb->num3 = 1024; 
$this->Plus9[18] = $bb = new BitBoard(); $bb->num1 = 32832; $bb->num2 = 8208; $bb->num3 = 2048; 
$this->Plus9[19] = $bb = new BitBoard(); $bb->num1 = 128; $bb->num2 = 16416; $bb->num3 = 4096; 
$this->Plus9[20] = $bb = new BitBoard(); $bb->num2 = 32832; $bb->num3 = 8192; 
$this->Plus9[21] = $bb = new BitBoard(); $bb->num2 = 128; $bb->num3 = 16384; 
$this->Plus9[22] = $bb = new BitBoard(); $bb->num3 = 32768; 
$this->Plus9[23] = $bb = new BitBoard(); 
$this->Plus9[24] = $bb = new BitBoard(); $bb->num1 = 4104; $bb->num2 = 1026; 
$this->Plus9[25] = $bb = new BitBoard(); $bb->num1 = 8208; $bb->num2 = 2052; 
$this->Plus9[26] = $bb = new BitBoard(); $bb->num1 = 16416; $bb->num2 = 4104; 
$this->Plus9[27] = $bb = new BitBoard(); $bb->num1 = 32832; $bb->num2 = 8208; 
$this->Plus9[28] = $bb = new BitBoard(); $bb->num1 = 128; $bb->num2 = 16416; 
$this->Plus9[29] = $bb = new BitBoard(); $bb->num2 = 32832; 
$this->Plus9[30] = $bb = new BitBoard(); $bb->num2 = 128; 
$this->Plus9[31] = $bb = new BitBoard(); 
$this->Plus9[32] = $bb = new BitBoard(); $bb->num1 = 2052; $bb->num2 = 512; 
$this->Plus9[33] = $bb = new BitBoard(); $bb->num1 = 4104; $bb->num2 = 1024; 
$this->Plus9[34] = $bb = new BitBoard(); $bb->num1 = 8208; $bb->num2 = 2048; 
$this->Plus9[35] = $bb = new BitBoard(); $bb->num1 = 16416; $bb->num2 = 4096; 
$this->Plus9[36] = $bb = new BitBoard(); $bb->num1 = 32832; $bb->num2 = 8192; 
$this->Plus9[37] = $bb = new BitBoard(); $bb->num1 = 128; $bb->num2 = 16384; 
$this->Plus9[38] = $bb = new BitBoard(); $bb->num2 = 32768; 
$this->Plus9[39] = $bb = new BitBoard(); 
$this->Plus9[40] = $bb = new BitBoard(); $bb->num1 = 1026; 
$this->Plus9[41] = $bb = new BitBoard(); $bb->num1 = 2052; 
$this->Plus9[42] = $bb = new BitBoard(); $bb->num1 = 4104; 
$this->Plus9[43] = $bb = new BitBoard(); $bb->num1 = 8208; 
$this->Plus9[44] = $bb = new BitBoard(); $bb->num1 = 16416; 
$this->Plus9[45] = $bb = new BitBoard(); $bb->num1 = 32832; 
$this->Plus9[46] = $bb = new BitBoard(); $bb->num1 = 128; 
$this->Plus9[47] = $bb = new BitBoard(); 
$this->Plus9[48] = $bb = new BitBoard(); $bb->num1 = 512; 
$this->Plus9[49] = $bb = new BitBoard(); $bb->num1 = 1024; 
$this->Plus9[50] = $bb = new BitBoard(); $bb->num1 = 2048; 
$this->Plus9[51] = $bb = new BitBoard(); $bb->num1 = 4096; 
$this->Plus9[52] = $bb = new BitBoard(); $bb->num1 = 8192; 
$this->Plus9[53] = $bb = new BitBoard(); $bb->num1 = 16384; 
$this->Plus9[54] = $bb = new BitBoard(); $bb->num1 = 32768; 
$this->Plus9[55] = $bb = new BitBoard(); 
$this->Plus9[56] = $bb = new BitBoard(); 
$this->Plus9[57] = $bb = new BitBoard(); 
$this->Plus9[58] = $bb = new BitBoard(); 
$this->Plus9[59] = $bb = new BitBoard(); 
$this->Plus9[60] = $bb = new BitBoard(); 
$this->Plus9[61] = $bb = new BitBoard(); 
$this->Plus9[62] = $bb = new BitBoard(); 
$this->Plus9[63] = $bb = new BitBoard(); 

$this->Minus1[0] = $bb = new BitBoard(); 
$this->Minus1[1] = $bb = new BitBoard(); $bb->num4 = 1;
$this->Minus1[2] = $bb = new BitBoard(); $bb->num4 = 3;
$this->Minus1[3] = $bb = new BitBoard(); $bb->num4 = 7;
$this->Minus1[4] = $bb = new BitBoard(); $bb->num4 = 15;
$this->Minus1[5] = $bb = new BitBoard(); $bb->num4 = 31;
$this->Minus1[6] = $bb = new BitBoard(); $bb->num4 = 63;
$this->Minus1[7] = $bb = new BitBoard(); $bb->num4 = 127;
$this->Minus1[8] = $bb = new BitBoard(); 
$this->Minus1[9] = $bb = new BitBoard(); $bb->num4 = 256;
$this->Minus1[10] = $bb = new BitBoard(); $bb->num4 = 768;
$this->Minus1[11] = $bb = new BitBoard(); $bb->num4 = 1792;
$this->Minus1[12] = $bb = new BitBoard(); $bb->num4 = 3840;
$this->Minus1[13] = $bb = new BitBoard(); $bb->num4 = 7936;
$this->Minus1[14] = $bb = new BitBoard(); $bb->num4 = 16128;
$this->Minus1[15] = $bb = new BitBoard(); $bb->num4 = 32512;
$this->Minus1[16] = $bb = new BitBoard(); 
$this->Minus1[17] = $bb = new BitBoard(); $bb->num3 = 1; 
$this->Minus1[18] = $bb = new BitBoard(); $bb->num3 = 3; 
$this->Minus1[19] = $bb = new BitBoard(); $bb->num3 = 7; 
$this->Minus1[20] = $bb = new BitBoard(); $bb->num3 = 15; 
$this->Minus1[21] = $bb = new BitBoard(); $bb->num3 = 31; 
$this->Minus1[22] = $bb = new BitBoard(); $bb->num3 = 63; 
$this->Minus1[23] = $bb = new BitBoard(); $bb->num3 = 127; 
$this->Minus1[24] = $bb = new BitBoard(); 
$this->Minus1[25] = $bb = new BitBoard(); $bb->num3 = 256; 
$this->Minus1[26] = $bb = new BitBoard(); $bb->num3 = 768; 
$this->Minus1[27] = $bb = new BitBoard(); $bb->num3 = 1792; 
$this->Minus1[28] = $bb = new BitBoard(); $bb->num3 = 3840; 
$this->Minus1[29] = $bb = new BitBoard(); $bb->num3 = 7936; 
$this->Minus1[30] = $bb = new BitBoard(); $bb->num3 = 16128; 
$this->Minus1[31] = $bb = new BitBoard(); $bb->num3 = 32512; 
$this->Minus1[32] = $bb = new BitBoard(); 
$this->Minus1[33] = $bb = new BitBoard(); $bb->num2 = 1; 
$this->Minus1[34] = $bb = new BitBoard(); $bb->num2 = 3; 
$this->Minus1[35] = $bb = new BitBoard(); $bb->num2 = 7; 
$this->Minus1[36] = $bb = new BitBoard(); $bb->num2 = 15; 
$this->Minus1[37] = $bb = new BitBoard(); $bb->num2 = 31; 
$this->Minus1[38] = $bb = new BitBoard(); $bb->num2 = 63; 
$this->Minus1[39] = $bb = new BitBoard(); $bb->num2 = 127; 
$this->Minus1[40] = $bb = new BitBoard(); 
$this->Minus1[41] = $bb = new BitBoard(); $bb->num2 = 256; 
$this->Minus1[42] = $bb = new BitBoard(); $bb->num2 = 768; 
$this->Minus1[43] = $bb = new BitBoard(); $bb->num2 = 1792; 
$this->Minus1[44] = $bb = new BitBoard(); $bb->num2 = 3840; 
$this->Minus1[45] = $bb = new BitBoard(); $bb->num2 = 7936; 
$this->Minus1[46] = $bb = new BitBoard(); $bb->num2 = 16128; 
$this->Minus1[47] = $bb = new BitBoard(); $bb->num2 = 32512; 
$this->Minus1[48] = $bb = new BitBoard(); 
$this->Minus1[49] = $bb = new BitBoard(); $bb->num1 = 1; 
$this->Minus1[50] = $bb = new BitBoard(); $bb->num1 = 3; 
$this->Minus1[51] = $bb = new BitBoard(); $bb->num1 = 7; 
$this->Minus1[52] = $bb = new BitBoard(); $bb->num1 = 15; 
$this->Minus1[53] = $bb = new BitBoard(); $bb->num1 = 31; 
$this->Minus1[54] = $bb = new BitBoard(); $bb->num1 = 63; 
$this->Minus1[55] = $bb = new BitBoard(); $bb->num1 = 127; 
$this->Minus1[56] = $bb = new BitBoard(); 
$this->Minus1[57] = $bb = new BitBoard(); $bb->num1 = 256; 
$this->Minus1[58] = $bb = new BitBoard(); $bb->num1 = 768; 
$this->Minus1[59] = $bb = new BitBoard(); $bb->num1 = 1792; 
$this->Minus1[60] = $bb = new BitBoard(); $bb->num1 = 3840; 
$this->Minus1[61] = $bb = new BitBoard(); $bb->num1 = 7936; 
$this->Minus1[62] = $bb = new BitBoard(); $bb->num1 = 16128; 
$this->Minus1[63] = $bb = new BitBoard(); $bb->num1 = 32512; 

$this->Minus7[0] = $bb = new BitBoard(); 
$this->Minus7[1] = $bb = new BitBoard(); 
$this->Minus7[2] = $bb = new BitBoard(); 
$this->Minus7[3] = $bb = new BitBoard(); 
$this->Minus7[4] = $bb = new BitBoard(); 
$this->Minus7[5] = $bb = new BitBoard(); 
$this->Minus7[6] = $bb = new BitBoard(); 
$this->Minus7[7] = $bb = new BitBoard(); 
$this->Minus7[8] = $bb = new BitBoard(); $bb->num4 = 2;
$this->Minus7[9] = $bb = new BitBoard(); $bb->num4 = 4;
$this->Minus7[10] = $bb = new BitBoard(); $bb->num4 = 8;
$this->Minus7[11] = $bb = new BitBoard(); $bb->num4 = 16;
$this->Minus7[12] = $bb = new BitBoard(); $bb->num4 = 32;
$this->Minus7[13] = $bb = new BitBoard(); $bb->num4 = 64;
$this->Minus7[14] = $bb = new BitBoard(); $bb->num4 = 128;
$this->Minus7[15] = $bb = new BitBoard(); 
$this->Minus7[16] = $bb = new BitBoard(); $bb->num4 = 516;
$this->Minus7[17] = $bb = new BitBoard(); $bb->num4 = 1032;
$this->Minus7[18] = $bb = new BitBoard(); $bb->num4 = 2064;
$this->Minus7[19] = $bb = new BitBoard(); $bb->num4 = 4128;
$this->Minus7[20] = $bb = new BitBoard(); $bb->num4 = 8256;
$this->Minus7[21] = $bb = new BitBoard(); $bb->num4 = 16512;
$this->Minus7[22] = $bb = new BitBoard(); $bb->num4 = 32768;
$this->Minus7[23] = $bb = new BitBoard(); 
$this->Minus7[24] = $bb = new BitBoard(); $bb->num3 = 2; $bb->num4 = 1032;
$this->Minus7[25] = $bb = new BitBoard(); $bb->num3 = 4; $bb->num4 = 2064;
$this->Minus7[26] = $bb = new BitBoard(); $bb->num3 = 8; $bb->num4 = 4128;
$this->Minus7[27] = $bb = new BitBoard(); $bb->num3 = 16; $bb->num4 = 8256;
$this->Minus7[28] = $bb = new BitBoard(); $bb->num3 = 32; $bb->num4 = 16512;
$this->Minus7[29] = $bb = new BitBoard(); $bb->num3 = 64; $bb->num4 = 32768;
$this->Minus7[30] = $bb = new BitBoard(); $bb->num3 = 128; 
$this->Minus7[31] = $bb = new BitBoard(); 
$this->Minus7[32] = $bb = new BitBoard(); $bb->num3 = 516; $bb->num4 = 2064;
$this->Minus7[33] = $bb = new BitBoard(); $bb->num3 = 1032; $bb->num4 = 4128;
$this->Minus7[34] = $bb = new BitBoard(); $bb->num3 = 2064; $bb->num4 = 8256;
$this->Minus7[35] = $bb = new BitBoard(); $bb->num3 = 4128; $bb->num4 = 16512;
$this->Minus7[36] = $bb = new BitBoard(); $bb->num3 = 8256; $bb->num4 = 32768;
$this->Minus7[37] = $bb = new BitBoard(); $bb->num3 = 16512; 
$this->Minus7[38] = $bb = new BitBoard(); $bb->num3 = 32768; 
$this->Minus7[39] = $bb = new BitBoard(); 
$this->Minus7[40] = $bb = new BitBoard(); $bb->num2 = 2; $bb->num3 = 1032; $bb->num4 = 4128;
$this->Minus7[41] = $bb = new BitBoard(); $bb->num2 = 4; $bb->num3 = 2064; $bb->num4 = 8256;
$this->Minus7[42] = $bb = new BitBoard(); $bb->num2 = 8; $bb->num3 = 4128; $bb->num4 = 16512;
$this->Minus7[43] = $bb = new BitBoard(); $bb->num2 = 16; $bb->num3 = 8256; $bb->num4 = 32768;
$this->Minus7[44] = $bb = new BitBoard(); $bb->num2 = 32; $bb->num3 = 16512; 
$this->Minus7[45] = $bb = new BitBoard(); $bb->num2 = 64; $bb->num3 = 32768; 
$this->Minus7[46] = $bb = new BitBoard(); $bb->num2 = 128; 
$this->Minus7[47] = $bb = new BitBoard(); 
$this->Minus7[48] = $bb = new BitBoard(); $bb->num2 = 516; $bb->num3 = 2064; $bb->num4 = 8256;
$this->Minus7[49] = $bb = new BitBoard(); $bb->num2 = 1032; $bb->num3 = 4128; $bb->num4 = 16512;
$this->Minus7[50] = $bb = new BitBoard(); $bb->num2 = 2064; $bb->num3 = 8256; $bb->num4 = 32768;
$this->Minus7[51] = $bb = new BitBoard(); $bb->num2 = 4128; $bb->num3 = 16512; 
$this->Minus7[52] = $bb = new BitBoard(); $bb->num2 = 8256; $bb->num3 = 32768; 
$this->Minus7[53] = $bb = new BitBoard(); $bb->num2 = 16512; 
$this->Minus7[54] = $bb = new BitBoard(); $bb->num2 = 32768; 
$this->Minus7[55] = $bb = new BitBoard(); 
$this->Minus7[56] = $bb = new BitBoard(); $bb->num1 = 2; $bb->num2 = 1032; $bb->num3 = 4128; $bb->num4 = 16512;
$this->Minus7[57] = $bb = new BitBoard(); $bb->num1 = 4; $bb->num2 = 2064; $bb->num3 = 8256; $bb->num4 = 32768;
$this->Minus7[58] = $bb = new BitBoard(); $bb->num1 = 8; $bb->num2 = 4128; $bb->num3 = 16512; 
$this->Minus7[59] = $bb = new BitBoard(); $bb->num1 = 16; $bb->num2 = 8256; $bb->num3 = 32768; 
$this->Minus7[60] = $bb = new BitBoard(); $bb->num1 = 32; $bb->num2 = 16512; 
$this->Minus7[61] = $bb = new BitBoard(); $bb->num1 = 64; $bb->num2 = 32768; 
$this->Minus7[62] = $bb = new BitBoard(); $bb->num1 = 128; 
$this->Minus7[63] = $bb = new BitBoard(); 

$this->Minus8[0] = $bb = new BitBoard(); 
$this->Minus8[1] = $bb = new BitBoard(); 
$this->Minus8[2] = $bb = new BitBoard(); 
$this->Minus8[3] = $bb = new BitBoard(); 
$this->Minus8[4] = $bb = new BitBoard(); 
$this->Minus8[5] = $bb = new BitBoard(); 
$this->Minus8[6] = $bb = new BitBoard(); 
$this->Minus8[7] = $bb = new BitBoard(); 
$this->Minus8[8] = $bb = new BitBoard(); $bb->num4 = 1;
$this->Minus8[9] = $bb = new BitBoard(); $bb->num4 = 2;
$this->Minus8[10] = $bb = new BitBoard(); $bb->num4 = 4;
$this->Minus8[11] = $bb = new BitBoard(); $bb->num4 = 8;
$this->Minus8[12] = $bb = new BitBoard(); $bb->num4 = 16;
$this->Minus8[13] = $bb = new BitBoard(); $bb->num4 = 32;
$this->Minus8[14] = $bb = new BitBoard(); $bb->num4 = 64;
$this->Minus8[15] = $bb = new BitBoard(); $bb->num4 = 128;
$this->Minus8[16] = $bb = new BitBoard(); $bb->num4 = 257;
$this->Minus8[17] = $bb = new BitBoard(); $bb->num4 = 514;
$this->Minus8[18] = $bb = new BitBoard(); $bb->num4 = 1028;
$this->Minus8[19] = $bb = new BitBoard(); $bb->num4 = 2056;
$this->Minus8[20] = $bb = new BitBoard(); $bb->num4 = 4112;
$this->Minus8[21] = $bb = new BitBoard(); $bb->num4 = 8224;
$this->Minus8[22] = $bb = new BitBoard(); $bb->num4 = 16448;
$this->Minus8[23] = $bb = new BitBoard(); $bb->num4 = 32896;
$this->Minus8[24] = $bb = new BitBoard(); $bb->num3 = 1; $bb->num4 = 257;
$this->Minus8[25] = $bb = new BitBoard(); $bb->num3 = 2; $bb->num4 = 514;
$this->Minus8[26] = $bb = new BitBoard(); $bb->num3 = 4; $bb->num4 = 1028;
$this->Minus8[27] = $bb = new BitBoard(); $bb->num3 = 8; $bb->num4 = 2056;
$this->Minus8[28] = $bb = new BitBoard(); $bb->num3 = 16; $bb->num4 = 4112;
$this->Minus8[29] = $bb = new BitBoard(); $bb->num3 = 32; $bb->num4 = 8224;
$this->Minus8[30] = $bb = new BitBoard(); $bb->num3 = 64; $bb->num4 = 16448;
$this->Minus8[31] = $bb = new BitBoard(); $bb->num3 = 128; $bb->num4 = 32896;
$this->Minus8[32] = $bb = new BitBoard(); $bb->num3 = 257; $bb->num4 = 257;
$this->Minus8[33] = $bb = new BitBoard(); $bb->num3 = 514; $bb->num4 = 514;
$this->Minus8[34] = $bb = new BitBoard(); $bb->num3 = 1028; $bb->num4 = 1028;
$this->Minus8[35] = $bb = new BitBoard(); $bb->num3 = 2056; $bb->num4 = 2056;
$this->Minus8[36] = $bb = new BitBoard(); $bb->num3 = 4112; $bb->num4 = 4112;
$this->Minus8[37] = $bb = new BitBoard(); $bb->num3 = 8224; $bb->num4 = 8224;
$this->Minus8[38] = $bb = new BitBoard(); $bb->num3 = 16448; $bb->num4 = 16448;
$this->Minus8[39] = $bb = new BitBoard(); $bb->num3 = 32896; $bb->num4 = 32896;
$this->Minus8[40] = $bb = new BitBoard(); $bb->num2 = 1; $bb->num3 = 257; $bb->num4 = 257;
$this->Minus8[41] = $bb = new BitBoard(); $bb->num2 = 2; $bb->num3 = 514; $bb->num4 = 514;
$this->Minus8[42] = $bb = new BitBoard(); $bb->num2 = 4; $bb->num3 = 1028; $bb->num4 = 1028;
$this->Minus8[43] = $bb = new BitBoard(); $bb->num2 = 8; $bb->num3 = 2056; $bb->num4 = 2056;
$this->Minus8[44] = $bb = new BitBoard(); $bb->num2 = 16; $bb->num3 = 4112; $bb->num4 = 4112;
$this->Minus8[45] = $bb = new BitBoard(); $bb->num2 = 32; $bb->num3 = 8224; $bb->num4 = 8224;
$this->Minus8[46] = $bb = new BitBoard(); $bb->num2 = 64; $bb->num3 = 16448; $bb->num4 = 16448;
$this->Minus8[47] = $bb = new BitBoard(); $bb->num2 = 128; $bb->num3 = 32896; $bb->num4 = 32896;
$this->Minus8[48] = $bb = new BitBoard(); $bb->num2 = 257; $bb->num3 = 257; $bb->num4 = 257;
$this->Minus8[49] = $bb = new BitBoard(); $bb->num2 = 514; $bb->num3 = 514; $bb->num4 = 514;
$this->Minus8[50] = $bb = new BitBoard(); $bb->num2 = 1028; $bb->num3 = 1028; $bb->num4 = 1028;
$this->Minus8[51] = $bb = new BitBoard(); $bb->num2 = 2056; $bb->num3 = 2056; $bb->num4 = 2056;
$this->Minus8[52] = $bb = new BitBoard(); $bb->num2 = 4112; $bb->num3 = 4112; $bb->num4 = 4112;
$this->Minus8[53] = $bb = new BitBoard(); $bb->num2 = 8224; $bb->num3 = 8224; $bb->num4 = 8224;
$this->Minus8[54] = $bb = new BitBoard(); $bb->num2 = 16448; $bb->num3 = 16448; $bb->num4 = 16448;
$this->Minus8[55] = $bb = new BitBoard(); $bb->num2 = 32896; $bb->num3 = 32896; $bb->num4 = 32896;
$this->Minus8[56] = $bb = new BitBoard(); $bb->num1 = 1; $bb->num2 = 257; $bb->num3 = 257; $bb->num4 = 257;
$this->Minus8[57] = $bb = new BitBoard(); $bb->num1 = 2; $bb->num2 = 514; $bb->num3 = 514; $bb->num4 = 514;
$this->Minus8[58] = $bb = new BitBoard(); $bb->num1 = 4; $bb->num2 = 1028; $bb->num3 = 1028; $bb->num4 = 1028;
$this->Minus8[59] = $bb = new BitBoard(); $bb->num1 = 8; $bb->num2 = 2056; $bb->num3 = 2056; $bb->num4 = 2056;
$this->Minus8[60] = $bb = new BitBoard(); $bb->num1 = 16; $bb->num2 = 4112; $bb->num3 = 4112; $bb->num4 = 4112;
$this->Minus8[61] = $bb = new BitBoard(); $bb->num1 = 32; $bb->num2 = 8224; $bb->num3 = 8224; $bb->num4 = 8224;
$this->Minus8[62] = $bb = new BitBoard(); $bb->num1 = 64; $bb->num2 = 16448; $bb->num3 = 16448; $bb->num4 = 16448;
$this->Minus8[63] = $bb = new BitBoard(); $bb->num1 = 128; $bb->num2 = 32896; $bb->num3 = 32896; $bb->num4 = 32896;

$this->Minus9[0] = $bb = new BitBoard(); 
$this->Minus9[1] = $bb = new BitBoard(); 
$this->Minus9[2] = $bb = new BitBoard(); 
$this->Minus9[3] = $bb = new BitBoard(); 
$this->Minus9[4] = $bb = new BitBoard(); 
$this->Minus9[5] = $bb = new BitBoard(); 
$this->Minus9[6] = $bb = new BitBoard(); 
$this->Minus9[7] = $bb = new BitBoard(); 
$this->Minus9[8] = $bb = new BitBoard(); 
$this->Minus9[9] = $bb = new BitBoard(); $bb->num4 = 1;
$this->Minus9[10] = $bb = new BitBoard(); $bb->num4 = 2;
$this->Minus9[11] = $bb = new BitBoard(); $bb->num4 = 4;
$this->Minus9[12] = $bb = new BitBoard(); $bb->num4 = 8;
$this->Minus9[13] = $bb = new BitBoard(); $bb->num4 = 16;
$this->Minus9[14] = $bb = new BitBoard(); $bb->num4 = 32;
$this->Minus9[15] = $bb = new BitBoard(); $bb->num4 = 64;
$this->Minus9[16] = $bb = new BitBoard(); 
$this->Minus9[17] = $bb = new BitBoard(); $bb->num4 = 256;
$this->Minus9[18] = $bb = new BitBoard(); $bb->num4 = 513;
$this->Minus9[19] = $bb = new BitBoard(); $bb->num4 = 1026;
$this->Minus9[20] = $bb = new BitBoard(); $bb->num4 = 2052;
$this->Minus9[21] = $bb = new BitBoard(); $bb->num4 = 4104;
$this->Minus9[22] = $bb = new BitBoard(); $bb->num4 = 8208;
$this->Minus9[23] = $bb = new BitBoard(); $bb->num4 = 16416;
$this->Minus9[24] = $bb = new BitBoard(); 
$this->Minus9[25] = $bb = new BitBoard(); $bb->num3 = 1; 
$this->Minus9[26] = $bb = new BitBoard(); $bb->num3 = 2; $bb->num4 = 256;
$this->Minus9[27] = $bb = new BitBoard(); $bb->num3 = 4; $bb->num4 = 513;
$this->Minus9[28] = $bb = new BitBoard(); $bb->num3 = 8; $bb->num4 = 1026;
$this->Minus9[29] = $bb = new BitBoard(); $bb->num3 = 16; $bb->num4 = 2052;
$this->Minus9[30] = $bb = new BitBoard(); $bb->num3 = 32; $bb->num4 = 4104;
$this->Minus9[31] = $bb = new BitBoard(); $bb->num3 = 64; $bb->num4 = 8208;
$this->Minus9[32] = $bb = new BitBoard(); 
$this->Minus9[33] = $bb = new BitBoard(); $bb->num3 = 256; 
$this->Minus9[34] = $bb = new BitBoard(); $bb->num3 = 513; 
$this->Minus9[35] = $bb = new BitBoard(); $bb->num3 = 1026; $bb->num4 = 256;
$this->Minus9[36] = $bb = new BitBoard(); $bb->num3 = 2052; $bb->num4 = 513;
$this->Minus9[37] = $bb = new BitBoard(); $bb->num3 = 4104; $bb->num4 = 1026;
$this->Minus9[38] = $bb = new BitBoard(); $bb->num3 = 8208; $bb->num4 = 2052;
$this->Minus9[39] = $bb = new BitBoard(); $bb->num3 = 16416; $bb->num4 = 4104;
$this->Minus9[40] = $bb = new BitBoard(); 
$this->Minus9[41] = $bb = new BitBoard(); $bb->num2 = 1; 
$this->Minus9[42] = $bb = new BitBoard(); $bb->num2 = 2; $bb->num3 = 256; 
$this->Minus9[43] = $bb = new BitBoard(); $bb->num2 = 4; $bb->num3 = 513; 
$this->Minus9[44] = $bb = new BitBoard(); $bb->num2 = 8; $bb->num3 = 1026; $bb->num4 = 256;
$this->Minus9[45] = $bb = new BitBoard(); $bb->num2 = 16; $bb->num3 = 2052; $bb->num4 = 513;
$this->Minus9[46] = $bb = new BitBoard(); $bb->num2 = 32; $bb->num3 = 4104; $bb->num4 = 1026;
$this->Minus9[47] = $bb = new BitBoard(); $bb->num2 = 64; $bb->num3 = 8208; $bb->num4 = 2052;
$this->Minus9[48] = $bb = new BitBoard(); 
$this->Minus9[49] = $bb = new BitBoard(); $bb->num2 = 256; 
$this->Minus9[50] = $bb = new BitBoard(); $bb->num2 = 513; 
$this->Minus9[51] = $bb = new BitBoard(); $bb->num2 = 1026; $bb->num3 = 256; 
$this->Minus9[52] = $bb = new BitBoard(); $bb->num2 = 2052; $bb->num3 = 513; 
$this->Minus9[53] = $bb = new BitBoard(); $bb->num2 = 4104; $bb->num3 = 1026; $bb->num4 = 256;
$this->Minus9[54] = $bb = new BitBoard(); $bb->num2 = 8208; $bb->num3 = 2052; $bb->num4 = 513;
$this->Minus9[55] = $bb = new BitBoard(); $bb->num2 = 16416; $bb->num3 = 4104; $bb->num4 = 1026;
$this->Minus9[56] = $bb = new BitBoard(); 
$this->Minus9[57] = $bb = new BitBoard(); $bb->num1 = 1; 
$this->Minus9[58] = $bb = new BitBoard(); $bb->num1 = 2; $bb->num2 = 256; 
$this->Minus9[59] = $bb = new BitBoard(); $bb->num1 = 4; $bb->num2 = 513; 
$this->Minus9[60] = $bb = new BitBoard(); $bb->num1 = 8; $bb->num2 = 1026; $bb->num3 = 256; 
$this->Minus9[61] = $bb = new BitBoard(); $bb->num1 = 16; $bb->num2 = 2052; $bb->num3 = 513; 
$this->Minus9[62] = $bb = new BitBoard(); $bb->num1 = 32; $bb->num2 = 4104; $bb->num3 = 1026; $bb->num4 = 256;
$this->Minus9[63] = $bb = new BitBoard(); $bb->num1 = 64; $bb->num2 = 8208; $bb->num3 = 2052; $bb->num4 = 513;

// Setup other boards.

// Setup casteling bits. These indicate which tiles need to be free to be able to castle.
$this->n64WhiteCastleKFree = $bb = new BitBoard(); $bb->num4 = 96;
$this->n64WhiteCastleQFree = $bb = new BitBoard(); $bb->num4 = 12;
$this->n64BlackCastleKFree = $bb = new BitBoard(); $bb->num1 = 24576; 
$this->n64BlackCastleQFree = $bb = new BitBoard(); $bb->num1 = 3072; 

// Set bits to mask ranks.
$this->Ranks[0] = $bb = new BitBoard(); $bb->num4 = 255;
$this->Ranks[1] = $bb = new BitBoard(); $bb->num4 = 65280;
$this->Ranks[2] = $bb = new BitBoard(); $bb->num3 = 255; 
$this->Ranks[3] = $bb = new BitBoard(); $bb->num3 = 65280; 
$this->Ranks[4] = $bb = new BitBoard(); $bb->num2 = 255; 
$this->Ranks[5] = $bb = new BitBoard(); $bb->num2 = 65280; 
$this->Ranks[6] = $bb = new BitBoard(); $bb->num1 = 255; 
$this->Ranks[7] = $bb = new BitBoard(); $bb->num1 = 65280; 

// Will mask the en passant tile.
$this->n64Enpassant = new BitBoard();
	
	}
	/// <summary>
	/// Sets up the squares that can be attacked for each piece type for all 64 positions on the board
	/// as well as populating some other bit boards.
	/// </summary>
	private function SetupAttackBitboardsOLD()
	{
		$n64One = new BitBoard('0x1');
		
		// Generate masks for every tile. Helps to reduce the number of bit shifting operations required.
		$this->Tiles = array();
		for ($nTile = 0; $nTile < 64; $nTile++)
			$this->Tiles[$nTile] = $n64One->shift_l_($nTile);

		// Start from index 0=A1 and work up to index 63=H8
		$this->KingAttacks = array();

		// The king can move 1 square in any direction. Of course it cannot move off the board :P
		for ($nTile = 0; $nTile < 64; $nTile++)
		{
			$this->KingAttacks[$nTile] = new BitBoard();
			if ($nTile % 8 < 7)  // Possible to move right
			{
				$this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile + 1]);    // Move right
				if ($this->floor[$nTile] < 7) $this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile + 9]); // Move up
				if ($this->floor[$nTile] > 0) $this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile - 7]); // Move down
			}
			if ($nTile % 8 > 0)  // Possible to move left
			{
				$this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile - 1]);    // Move left.
				if ($this->floor[$nTile] < 7) $this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile + 7]); // Move up
				if ($this->floor[$nTile] > 0) $this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile - 9]); // Move down
			}
			if ($this->floor[$nTile] < 7) $this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile + 8]); //Move up
			if ($this->floor[$nTile] > 0) $this->KingAttacks[$nTile]->_OR($this->Tiles[$nTile - 8]); //Move down
		}

		// Knight moves.
		$this->KnightAttacks = array();
		for ($nTile = 0; $nTile < 64; $nTile++)
		{
			$this->KnightAttacks[$nTile] = new BitBoard();
			// Moving up 2 tiles, need 2 tile buffer
			if ($this->floor[$nTile] < 6)
			{
				if ($nTile % 8 < 7) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile + 17]);  // Moved right
				if ($nTile % 8 > 0) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile + 15]);  // Moved left
			}
			// Moving up 1 tile, need 1 tile buffer
			if ($this->floor[$nTile] < 7)
			{
				if ($nTile % 8 < 6) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile + 10]);  // Moved right
				if ($nTile % 8 > 1) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile + 6]);   // Moved left
			}
			// Moving down 2 tiles, need 2 tile buffer
			if ($this->floor[$nTile] > 1)
			{
				if ($nTile % 8 < 7) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile - 15]);  // Moved right
				if ($nTile % 8 > 0) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile - 17]);  // Moved left
			}
			// Moving down 1 tile, need 1 tile buffer
			if ($this->floor[$nTile] > 0)
			{
				if ($nTile % 8 < 6) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile - 6]);   // Moved right
				if ($nTile % 8 > 1) $this->KnightAttacks[$nTile]->_OR($this->Tiles[$nTile - 10]);  // Moved left
			}
		}

		// Moves/capture for white pawns. Starting on rank 1 and ending on rank 6
		$this->WPawnAttacks = array();
		$this->WPawnCaptures = array();
		for ($nTile = 0; $nTile < 64; $nTile++)
		{
			$this->WPawnAttacks[$nTile] = new BitBoard();
			$this->WPawnCaptures[$nTile] = new BitBoard();
			if ($this->floor[$nTile] < 1 || $this->floor[$nTile] > 6) continue;
			if ($this->floor[$nTile] == 1)  // Can move forward two tiles
				$this->WPawnAttacks[$nTile]->_OR($this->Tiles[$nTile + 16]);
			$this->WPawnAttacks[$nTile]->_OR($this->Tiles[$nTile + 8]);       // Move forward one tile.
			if ($nTile % 8 > 0)
				$this->WPawnCaptures[$nTile]->_OR($this->Tiles[$nTile + 7]);   // Take piece to the left.
			if ($nTile % 8 < 7)
				$this->WPawnCaptures[$nTile]->_OR($this->Tiles[$nTile + 9]);   // Take piece to the right.
		}
		// Moves/captures for black pawns. Going from rank 1 to rank 6
		$this->BPawnAttacks = array();
		$this->BPawnCaptures = array();
		for ($nTile = 0; $nTile < 64; $nTile++)
		{
			$this->BPawnAttacks[$nTile] = new BitBoard();
			$this->BPawnCaptures[$nTile] = new BitBoard();
			if ($this->floor[$nTile] < 1 || $this->floor[$nTile] > 6) continue;
			if ($this->floor[$nTile] == 6)  // Can move backward two tiles
				$this->BPawnAttacks[$nTile]->_OR($this->Tiles[$nTile - 16]);
			$this->BPawnAttacks[$nTile]->_OR($this->Tiles[$nTile - 8]);       // Move forward one tile.
			if ($nTile % 8 > 0)
				$this->BPawnCaptures[$nTile]->_OR($this->Tiles[$nTile - 9]);   // Take piece to the left.
			if ($nTile % 8 < 7)
				$this->BPawnCaptures[$nTile]->_OR($this->Tiles[$nTile - 7]);   // Take piece to the right.
		}

		// Moves in straigh lines (vertical, horizontal and diagonal)
		$this->Plus1 = array();
		$this->Plus8 = array();
		$this->Plus9 = array();
		$this->Plus7 = array();
		$this->Minus1 = array();
		$this->Minus7 = array();
		$this->Minus8 = array();
		$this->Minus9 = array();

		for ($nTile = 0; $nTile < 64; $nTile++)
		{
			$this->Plus1[$nTile] = new BitBoard();
			$this->Plus8[$nTile] = new BitBoard();
			$this->Plus9[$nTile] = new BitBoard();
			$this->Plus7[$nTile] = new BitBoard();
			$this->Minus1[$nTile] = new BitBoard();
			$this->Minus8[$nTile] = new BitBoard();
			// going right
			for ($file = $nTile % 8 + 1; $file < 8; $file++)
				$this->Plus1[$nTile]->_OR($this->Tiles[($this->floor[$nTile] * 8) + $file]);
			// going left
			for ($file = 0; $file < $nTile % 8; $file++)
				$this->Minus1[$nTile]->_OR($this->Tiles[($this->floor[$nTile] * 8) + $file]);
			// going up
			for ($rank = $this->floor[$nTile] + 1; $rank < 8; $rank++)
				$this->Plus8[$nTile]->_OR($this->Tiles[$rank * 8 + $nTile % 8]);
			// going down
			for ($rank = 0; $rank < $this->floor[$nTile]; $rank++)
				$this->Minus8[$nTile]->_OR($this->Tiles[$rank * 8 + $nTile % 8]);
			// going up right
			for ($rank = $this->floor[$nTile] + 1, $file = $nTile % 8 + 1; $rank < 8 && $file < 8; $rank++, $file++)
				$this->Plus9[$nTile]->_OR($this->Tiles[$rank * 8 + $file]);
			// going up left
			for ($rank = $this->floor[$nTile] + 1, $file = $nTile % 8 - 1; $rank < 8 && $file > -1; $rank++, $file--)
				$this->Plus7[$nTile]->_OR($this->Tiles[$rank * 8 + $file]);
		}
		// This time going backwards because it was just easier to come up with a solution.
		for($i = 0; $i < 64; $i++)
		{
			$this->Minus7[$i] = new BitBoard();
			$this->Minus9[$i] = new BitBoard();
		}
		for ($nTile = 63; $nTile > -1; $nTile--)
		{
			$this->Minus7[$nTile] = new BitBoard();
			$this->Minus9[$nTile] = new BitBoard();
			// going down left
			for ($rank = $this->floor[$nTile] - 1, $file = $nTile % 8 - 1; $rank > -1 && $file > -1; $rank--, $file--)
				$this->Minus9[$nTile]->_OR($this->Tiles[$rank * 8 + $file]);
			// going down right
			for ($rank = $this->floor[$nTile] - 1, $file = $nTile % 8 + 1; $rank > -1 && $file < 8; $rank--, $file++)
				$this->Minus7[$nTile]->_OR($this->Tiles[$rank * 8 + $file]);
		}
		// Setup other boards.

		// Setup casteling bits. These indicate which tiles need to be free to be able to castle.
		$this->n64WhiteCastleQFree = new BitBoard('0xC'); // 4 + 8
		$this->n64WhiteCastleKFree = new BitBoard('0x60'); // 32 + 64
		$this->n64BlackCastleQFree = $this->n64WhiteCastleQFree->shift_l_(56);
		$this->n64BlackCastleKFree = $this->n64WhiteCastleKFree->shift_l_(56);

		// Set bits to mask ranks.
		$this->Ranks = array();
		$mask = new BitBoard('0xFF');		// Masks a whole rank
		$this->Ranks[0] = $mask;
		$this->Ranks[1] = $mask->shift_l_(8);
		$this->Ranks[2] = $mask->shift_l_(16);
		$this->Ranks[3] = $mask->shift_l_(24);
		$this->Ranks[4] = $mask->shift_l_(32);
		$this->Ranks[5] = $mask->shift_l_(40);
		$this->Ranks[6] = $mask->shift_l_(48);
		$this->Ranks[7] = $mask->shift_l_(56);

		// Will mask the en passant tile.
		$this->n64Enpassant = new BitBoard();

	}

	/// <summary>
	/// Find the tiles that can be potentially attacked by a bishop (ie in the diagonal directions)
	/// </summary>
	/// <param name="nRank">The rank for the starting tile.</param>
	/// <param name="nFile">The file for the starting tile.</param>
	/// <returns>Returns a bitmap representing all the tiles that can be potentially attacked.</returns>
	private function FindTilesAttackedByBishop($nRank, $nFile)
	{
		$this->nCalls++;
		$nTile = $nRank * 8 + $nFile; $nBlockingTile = 0;
		// Find tiles to attack in the four diagonal directions
		$n64Blockers = BitBoard::_AND_($this->n64All, $this->Plus7[$nTile]);          // Find pieces sitting in the path from nTile.
		$nBlockingTile = $n64Blockers->get_pos_of_first_one_bit(); // $this->ChessUtils->FindFirstOneBit($n64Blockers);       // Find the position of the first bit set to 1.
		// Gets the bitboard containing the tiles that can be attacked from the blocking tile and then 
		// exclusive ORs it with the attacked tile for the current tile on. This means that if a portion of
		// the line is overlapped, it gets set to 0 and we end up with just the tiles that can be attacked.
		$n64AttackedTiles = BitBoard::_XOR_($this->Plus7[$nTile], $this->Plus7[$nBlockingTile]);

		$n64Blockers = BitBoard::_AND_($this->n64All, $this->Plus9[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_first_one_bit(); // $this->ChessUtils->FindFirstOneBit($n64Blockers);
		$n64AttackedTiles->_OR(BitBoard::_XOR_($this->Plus9[$nTile], $this->Plus9[$nBlockingTile]));

		// Now do the minus directions. Need to find the last bit set to 1.
		$n64Blockers = BitBoard::_AND_($this->n64All, $this->Minus7[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_last_one_bit(); // $this->ChessUtils->FindLastOneBit($n64Blockers);
		$n64AttackedTiles->_OR(BitBoard::_XOR_($this->Minus7[$nTile], $this->Minus7[$nBlockingTile]));

		$n64Blockers = BitBoard::_AND_($this->n64All, $this->Minus9[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_last_one_bit(); // $this->ChessUtils->FindLastOneBit($n64Blockers);
		$n64AttackedTiles->_OR(BitBoard::_XOR_($this->Minus9[$nTile], $this->Minus9[$nBlockingTile]));

		return $n64AttackedTiles;
	}

	/// <summary>
	/// Find the tiles that can be potentially attacked by a rook (ie in the vertical/horizontal directions)
	/// </summary>
	/// <param name="nRank">The rank for the starting tile.</param>
	/// <param name="nFile">The file for the starting tile.</param>
	/// <returns>Returns a bitmap representing all the tiles that can be potentially attacked.</returns>
	private function FindTilesAttackedByRook($nRank, $nFile)
	{
		// $this->nCalls++;
		// $nTile = $nRank * 8 + $nFile; $nBlockingTile = 0;
		// // Find tiles to attack in the four horizontal/vertical directions
		// $n64Blockers = BitBoard::_AND_($this->n64All, $this->Plus8[$nTile]);
		// $nBlockingTile = $n64Blockers->get_pos_of_first_one_bit(); // $this->ChessUtils->FindFirstOneBit($n64Blockers);
		// $n64AttackedTiles = BitBoard::_XOR_($this->Plus8[$nTile], $this->Plus8[$nBlockingTile]);

		// $n64Blockers = BitBoard::_AND_($this->n64All, $this->Plus1[$nTile]);
		// $nBlockingTile = $n64Blockers->get_pos_of_first_one_bit(); // $this->ChessUtils->FindFirstOneBit($n64Blockers);
		// $n64AttackedTiles->_OR(BitBoard::_XOR_($this->Plus1[$nTile], $this->Plus1[$nBlockingTile]));

		// $n64Blockers = BitBoard::_AND_($this->n64All, $this->Minus8[$nTile]);
		// $nBlockingTile = $n64Blockers->get_pos_of_last_one_bit(); // $this->ChessUtils->FindLastOneBit($n64Blockers);
		// $n64AttackedTiles->_OR(BitBoard::_XOR_($this->Minus8[$nTile], $this->Minus8[$nBlockingTile]));

		// $n64Blockers = BitBoard::_AND_($this->n64All, $this->Minus1[$nTile]);
		// $nBlockingTile = $n64Blockers->get_pos_of_last_one_bit(); // $this->ChessUtils->FindLastOneBit($n64Blockers);
		// $n64AttackedTiles->_OR(BitBoard::_XOR_($this->Minus1[$nTile], $this->Minus1[$nBlockingTile]));

		// return $n64AttackedTiles;
		
		// NOTE: is this way faster? It creates less duplicates.
		$this->nCalls++;
		$nTile = $nRank * 8 + $nFile; $nBlockingTile = 0; $tmp = new BitBoard();
		// Find tiles to attack in the four horizontal/vertical directions
		$n64Blockers = $tmp->_OR($this->n64All)->_AND($this->Plus8[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_first_one_bit(); // $this->ChessUtils->FindFirstOneBit($n64Blockers);
		$n64AttackedTiles = BitBoard::_XOR_($this->Plus8[$nTile], $this->Plus8[$nBlockingTile]);

		$tmp->set_to_zero();
		$n64Blockers = $tmp->_OR($this->n64All)->_AND($this->Plus1[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_first_one_bit(); // $this->ChessUtils->FindFirstOneBit($n64Blockers);
		$n64AttackedTiles->_OR(BitBoard::_XOR_($this->Plus1[$nTile], $this->Plus1[$nBlockingTile]));

		$tmp->set_to_zero();
		$n64Blockers = $tmp->_OR($this->n64All)->_AND($this->Minus8[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_last_one_bit(); // $this->ChessUtils->FindLastOneBit($n64Blockers);
		$n64AttackedTiles->_OR(BitBoard::_XOR_($this->Minus8[$nTile], $this->Minus8[$nBlockingTile]));

		$tmp->set_to_zero();
		$n64Blockers = $tmp->_OR($this->n64All)->_AND($this->Minus1[$nTile]);
		$nBlockingTile = $n64Blockers->get_pos_of_last_one_bit(); // $this->ChessUtils->FindLastOneBit($n64Blockers);
		$n64AttackedTiles->_OR(BitBoard::_XOR_($this->Minus1[$nTile], $this->Minus1[$nBlockingTile]));

		return $n64AttackedTiles;
	}

	/// <summary>
	/// Find the tiles that can be potentially attacked by a queen (all 8 directions).
	/// </summary>
	/// <param name="nRank">The rank of the starting tile.</param>
	/// <param name="nFile">The file for the starting tile.</param>
	/// <returns>Returns a bitmap representing all the tiles that can be potentially attacked.</returns>
	private function FindTilesAttackedByQueen($nRank, $nFile)
	{
		$this->nCalls++;
		$n64AttackedTiles = 0;
		// Use the rook and bishop functions to find the tiles attacked in all 8 directions.
		$n64AttackedTiles = $this->FindTilesAttackedByBishop($nRank, $nFile)->_OR($this->FindTilesAttackedByRook($nRank, $nFile));
		return $n64AttackedTiles;
	}

	/// <summary>
	/// Finds the tiles that can be potentially attacked by a knight.
	/// </summary>
	/// <param name="nRank">The rank of the starting tile.</param>
	/// <param name="nFile">The file of the starting tile.</param>
	/// <returns>Returns a bitmap representing the tiles that can be potentially attacked.</returns>
	private function FindTilesAttackedByKnight($nRank, $nFile)
	{
		$this->nCalls++;
		return $this->KnightAttacks[$nRank * 8 + $nFile]->duplicate();
	}

	/// <summary>
	/// Finds the tiles that can be potentially attacked by a king (does not include castling!!!)
	/// </summary>
	/// <param name="nRank">The rank for the starting tile.</param>
	/// <param name="nFile">The file for the starting tile.</param>
	/// <returns>Returns a bitmap representing the tiles that can be potentially attacked.</returns>
	private function FindTilesAttackedByKing($nRank, $nFile)
	{
		$this->nCalls++;
		return $this->KingAttacks[$nRank * 8 + $nFile]->duplicate();
	}

	/// <summary>
	/// Finds the tiles that can be potentially attacked by the pawn.
	/// </summary>
	/// <param name="nRank">The rank for the starting tile.</param>
	/// <param name="nFile">The file for the starting tile.</param>
	/// <returns>Returns a bitmap representing the tiles that can be potentially attacked.</returns>
	private function FindTilesAttackedByPawn($nRank, $nFile)
	{
		$this->nCalls++;
		$nTile = $nRank * 8 + $nFile;
		$n64AttackedTiles = 0; $n64Occupied = 0;

		// Need to use different bitmap arrays for the players.
		if ($this->PlayerTurn == 0)
		{
			// Get the tile(s) that can be theoretically moved to. 
			$n64AttackedTiles = $this->WPawnAttacks[$nTile]->duplicate();
			
			// If this is the starting rank for pawn then see if the two tiles above are free.
			if ($nRank == 1)
			{
				// Check that the tile in rank 3 is free. If not then rank 4 cannot be reached.
				$n64Occupied = BitBoard::_AND_($this->n64All, $this->Tiles[$nTile + 8]);
				if ($n64Occupied->is_zero())
				{
					// Check if rank 4 is free. If not then exclusive or to remove that tile from
					// the bitmap.
					$n64Occupied = BitBoard::_AND_($this->n64All, $this->Tiles[$nTile + 16]);
					if (!$n64Occupied->is_zero()) $n64AttackedTiles->_XOR($n64Occupied);
				}
				else
					$n64AttackedTiles->set_value('0x0');   // Set to 0 since neither tile can be reached.
			}
			else if ($nRank < 7)
			{
				// Check that the rank above can be moved to by masking the ALL pieces bitmap
				// with the tile above the current position, to see if the tile is occupied. 
				// If it is then remove this tile from the attack bitmap.
				$n64Occupied = BitBoard::_AND_($this->n64All, $this->Tiles[$nTile + 8]);
				if (!$n64Occupied->is_zero())
				{
					$remove = new BitBoard();
					$remove->set_value('FFFFFFFFFFFFFFFF');
					$remove->_XOR($this->Tiles[$nTile + 8]);
					$n64AttackedTiles->_AND($remove);
				}
			}
			// Now check if capturing a piece is possible by ANDing the captures bitmap
			// with the all black pieces bitmap and combining this with the AttackedTiles bitmap.
			$n64AttackedTiles->_OR(BitBoard::_AND_($this->WPawnCaptures[$nTile], $this->n64BAll));
			// Check if en passant is possible, by ANDing the en passant bitmap with the pawn capture 
			// bitmap for this tile. Then OR this with the attackedtiles bitmap.
			$n64AttackedTiles->_OR(BitBoard::_AND_($this->n64Enpassant, $this->WPawnCaptures[$nTile]));
		}
		else
		{
			$n64AttackedTiles = $this->BPawnAttacks[$nTile]->duplicate();
			if ($nRank == 6)
			{
				$n64Occupied = BitBoard::_AND_($this->n64All, $this->Tiles[$nTile - 8]);
				if ($n64Occupied->is_zero())
				{
					$n64Occupied = BitBoard::_AND_($this->n64All, $this->Tiles[$nTile - 16]);
					if (!$n64Occupied->is_zero()) $n64AttackedTiles->_XOR($n64Occupied);
				}
				else
					$n64AttackedTiles->set_value('0x0');   // Set to 0 since neither tile can be reached.
			}
			else if ($nRank > 0)
			{
				$n64Occupied = BitBoard::_AND_($this->n64All, $this->Tiles[$nTile - 8]);
				if (!$n64Occupied->is_zero())
				{
					$remove = new BitBoard();
					$remove->set_value('FFFFFFFFFFFFFFFF');
					$remove->_XOR($this->Tiles[$nTile - 8]);
					$n64AttackedTiles->_AND($remove);
				}
			}
			$n64AttackedTiles->_OR(BitBoard::_AND_($this->BPawnCaptures[$nTile], $this->n64WAll));
			$n64AttackedTiles->_OR(BitBoard::_AND_($this->n64Enpassant, $this->BPawnCaptures[$nTile]));
		}
		return $n64AttackedTiles;
	}

	/// <summary>
	/// Returns a bitmap representing the tiles that a king can castle to.
	/// </summary>
	/// <param name="nSide">The side to get the castling tiles for.</param>
	/// <returns>Returns a bitmap representing the tiles that can be castled to.</returns>
	private function FindTilesToCastleTo($nSide)
	{
		$this->nCalls++;
		// Find which side has the turn.
		if ($nSide == 0)
		{
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nKingTile = $tmp[0];
			$nRank = $nKingTile / 8;
			$nFile = $nKingTile % 8;
			// If the king is located at the starting position it can potentially castle.
			// Return the castle bitmap bitmap.
			if ($nRank == 0 && $nFile == 4)
				return $this->n64WhiteCastle;
		}
		else
		{
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nKingTile = $tmp[0];
			$nRank = $nKingTile / 8;
			$nFile = $nKingTile % 8;
			if ($nRank == 7 && $nFile == 4)
				return $this->n64BlackCastle;
		}
		return 0;
	}

	/// <summary>
	/// Finds the tiles that can be attacked(regardless of colour) by a piece on the given rank and file.
	/// </summary>
	/// <param name="nRank">The rank of the starting tile.</param>
	/// <param name="nFile">The file of the starting tile.</param>
	/// <returns>Returns a bitmap representing all the tiles that can be attacked.</returns>
	private function FindAttacksFromTile($nRank, $nFile)
	{
		$this->nCalls++;
		$n64Tiles = new BitBoard();
		// Find if there is a piece on this tile and then call the corresponding
		// functions for the piece type.
		if ($this->Board[$nRank][$nFile] == PIECE_TYPE::UNDEFINED)
			return $n64Tiles;
		if ($this->Board[$nRank][$nFile] == PIECE_TYPE::ROOK)
			$n64Tiles = $this->FindTilesAttackedByRook($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::QUEEN)
			$n64Tiles = $this->FindTilesAttackedByQueen($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::PAWN)
			$n64Tiles = $this->FindTilesAttackedByPawn($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KNIGHT)
			$n64Tiles = $this->FindTilesAttackedByKnight($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KING)
			$n64Tiles = $this->FindTilesAttackedByKing($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::BISHOP)
			$n64Tiles = $this->FindTilesAttackedByBishop($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::ROOK + 6)
			$n64Tiles = $this->FindTilesAttackedByRook($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::QUEEN + 6)
			$n64Tiles = $this->FindTilesAttackedByQueen($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::PAWN + 6)
			$n64Tiles = $this->FindTilesAttackedByPawn($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KNIGHT + 6)
			$n64Tiles = $this->FindTilesAttackedByKnight($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::KING + 6)
			$n64Tiles = $this->FindTilesAttackedByKing($nRank, $nFile);
		else if ($this->Board[$nRank][$nFile] == PIECE_TYPE::BISHOP + 6)
			$n64Tiles = $this->FindTilesAttackedByBishop($nRank, $nFile);

		return $n64Tiles;
	}

	/// <summary>
	/// Find all tiles that can attack the given tile (excludes normal pawn moves. Use FindMovesToTile instead if normal pawn moves are needed as well).
	/// </summary>
	/// <param name="nRank">The rank of the tile to test.</param>
	/// <param name="nFile">The file of the tile to test.</param>
	/// <returns>Returns a bitmap representing pieces for both sides that can attack the given tile.</returns>
	private function FindAttacksToTile($nRank, $nFile)
	{
		$this->nCalls++;
		// Bitmaps for storing attacking pieces.
		$Kings; $Knights; $Pawns; $Rooks; $Bishops;

		// Find the pieces a king/knight/pawn/rook/bishop could attack from this position.
		// Queens will show up in the rook and bishop bitmaps.
		$Kings = $this->FindTilesAttackedByKing($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64BKing, $this->n64WKing));
		$Bishops = $this->FindTilesAttackedByBishop($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64BBishops, $this->n64WBishops)->_OR($this->n64WQueens)->_OR($this->n64BQueens));
		$Rooks = $this->FindTilesAttackedByRook($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64BRooks, $this->n64WRooks)->_OR($this->n64WQueens)->_OR($this->n64BQueens));
		$Knights = $this->FindTilesAttackedByKnight($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64WKnights, $this->n64BKnights));
		if ($this->PlayerTurn == 0)
		{
			// Mask the returned bitmaps with the opponent's pawns to see which 'attacks'
			// are actual captures.
			$Pawns = $this->FindTilesAttackedByPawn($nRank, $nFile)->_AND($this->n64BPawns);
			$this->PlayerTurn = 1;
			$Pawns->_OR($this->FindTilesAttackedByPawn($nRank, $nFile)->_AND($this->n64WPawns));
			$this->PlayerTurn = 0;
		}
		else
		{
			$Pawns = $this->FindTilesAttackedByPawn($nRank, $nFile)->_AND($this->n64WPawns);
			$this->PlayerTurn = 0;
			$Pawns->_OR($this->FindTilesAttackedByPawn($nRank, $nFile)->_AND($this->n64BPawns));
			$this->PlayerTurn = 1;
		}

		// Combine all bitmaps and return.
		return $Kings->_OR($Bishops)->_OR($Rooks)->_OR($Knights)->_OR($Pawns);
	}

	/// <summary>
	/// Finds tiles that have pieces which can move to the specified tile (and capture if need be).
	/// </summary>
	/// <param name="nRank">The rank of the tile to test.</param>
	/// <param name="nFile">The file of the tile to test.</param>
	/// <returns>A bitmap representing pieces for both sides that can move to a given tile.</returns>
	private function FindMovesToTile($nRank, $nFile)
	{
		$this->nCalls++;
		$Kings; $Knights; $Pawns; $Rooks; $Bishops;   // Bitmaps for storing attacking pieces.
		$n64One = new BitBoard('0x1');

		// Find the pieces a king/knight/rook/bishop could attack from this position.
		// Queens will show up in the rook and bishop bitmaps.
		$Kings = $this->FindTilesAttackedByKing($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64BKing, $this->n64WKing));
		$Bishops = $this->FindTilesAttackedByBishop($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64BBishops, $this->n64WBishops)->_OR($this->n64WQueens)->_OR($this->n64BQueens));
		$Rooks = $this->FindTilesAttackedByRook($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64BRooks, $this->n64WRooks)->_OR($this->n64WQueens)->_OR($this->n64BQueens));
		$Knights = $this->FindTilesAttackedByKnight($nRank, $nFile)->_AND(BitBoard::_OR_($this->n64WKnights, $this->n64BKnights));
		$Pawns = new BitBoard();
		// Pawns are handled differently. The pawn's move to bitmap is taken and ANDed with the tile
		// provided to the function so we know if the pawn can move to that tile. The pawn square is
		// usually one more/less than the rank, unless the rank is 3 or 4 in which case the starting
		// rank for the pawn may also be checked to see if the pawn can jump two tiles. 
		if ($nRank > 0 && !$this->WPawnAttacks[($nRank - 1) * 8 + $nFile]->is_zero() && !$this->Tiles[$nRank * 8 + $nFile]->is_zero())
		{
			if (!BitBoard::_AND_($this->n64WPawns, $this->Tiles[($nRank - 1) * 8 + $nFile])->is_zero())
				$Pawns = $this->Tiles[($nRank - 1) * 8 + $nFile]->duplicate();
		}
		if ($nRank == 3)
		{
			// Need to check if there is a blocking piece before this pawn can be added.
			if(!BitBoard::_AND_($this->n64WPawns, $this->Tiles[($nRank - 2) * 8 + $nFile])->is_zero() && 
				BitBoard::_AND_($this->n64All, $this->Tiles[($nRank - 1) * 8 + $nFile])->is_zero())
				$Pawns->_OR($this->Tiles[($nRank - 2) * 8 + $nFile]);
		}
		if ($nRank < 7 && !$this->BPawnAttacks[($nRank + 1) * 8 + $nFile]->is_zero() && !$this->Tiles[$nRank * 8 + $nFile]->is_zero())
		{
			if (!BitBoard::_AND_($this->n64BPawns, $this->Tiles[($nRank + 1) * 8 + $nFile])->is_zero())
				$Pawns->_OR($this->Tiles[($nRank + 1) * 8 + $nFile]);
		}
		if ($nRank == 4)
		{
			if (!BitBoard::_AND_($this->n64BPawns, $this->Tiles[($nRank + 2) * 8 + $nFile])->is_zero() &&
				BitBoard::_AND_($this->n64All, $this->Tiles[($nRank + 1) * 8 + $nFile])->is_zero())
				$Pawns->_OR($this->Tiles[($nRank + 2) * 8 + $nFile]);
		}
		//if ($this->PlayerTurn == 0)
		//{
		//    Pawns = FindTilesAttackedByPawn(nRank, nFile) & $this->n64BPawns;
		//    // If not the bottom two ranks we can check if a pawn can move to this rank.
		//    if (nRank > 1)
		//    {
		//        // See if there is a pawn one rank below. If so add it to the bitmap.
		//        if (($this->n64WPawns & $this->Tiles[(nRank - 1) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank - 1) * 8 + nFile];
		//        // As there is no pawn one rank below, check if this is rank 3 which means
		//        // a pawn two ranks below in rank 1 could move up to here as long as there
		//        // is no piece in the way
		//        else if (nRank == 3 && ($this->n64WPawns & $this->Tiles[(nRank - 2) * 8 + nFile]) > 0 &&
		//                 ($this->n64All & $this->Tiles[(nRank - 2) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank - 2) * 8 + nFile];
		//    }
		//    $this->PlayerTurn = 1;
		//    Pawns |= FindTilesAttackedByPawn(nRank, nFile) & $this->n64WPawns;
		//    if (nRank < 6)
		//    {
		//        if (($this->n64BPawns & $this->Tiles[(nRank + 1) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank + 1) * 8 + nFile];
		//        else if (nRank == 4 && ($this->n64BPawns & $this->Tiles[(nRank + 2) * 8 + nFile]) > 0 &&
		//                 ($this->n64All & $this->Tiles[(nRank + 2) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank + 2) * 8 + nFile];
		//    }
		//    $this->PlayerTurn = 0;
		//}
		//else if($this->PlayerTurn == 1)
		//{
		//    Pawns = FindTilesAttackedByPawn(nRank, nFile) & $this->n64WPawns;
		//    if (nRank < 6)
		//    {
		//        if (($this->n64BPawns & $this->Tiles[(nRank + 1) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank + 1) * 8 + nFile];
		//        else if (nRank == 4 && ($this->n64BPawns & $this->Tiles[(nRank + 2) * 8 + nFile]) > 0 &&
		//                 ($this->n64All & $this->Tiles[(nRank + 2) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank + 2) * 8 + nFile];
		//    }
		//    $this->PlayerTurn = 0;
		//    Pawns |= FindTilesAttackedByPawn(nRank, nFile) & $this->n64BPawns;
		//    if (nRank > 1)
		//    {
		//        if (($this->n64WPawns & $this->Tiles[(nRank - 1) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank - 1) * 8 + nFile];
		//        else if (nRank == 3 && ($this->n64WPawns & $this->Tiles[(nRank - 2) * 8 + nFile]) > 0 &&
		//                 ($this->n64All & $this->Tiles[(nRank - 2) * 8 + nFile]) > 0)
		//            Pawns |= $this->Tiles[(nRank - 2) * 8 + nFile];
		//    }
		//    $this->PlayerTurn = 1;
		//}
		// Combine all bitmaps and return.
		return $Kings->_OR($Bishops)->_OR($Rooks)->_OR($Knights)->_OR($Pawns);
	}

	/// <summary>
	/// Returns a list of tiles between two tiles. The two tiles must be in a line.
	/// </summary>
	/// <param name="nRank1">The rank for tile1.</param>
	/// <param name="nFile1">The file for tile1.</param>
	/// <param name="nRank2">The rank for tile2.</param>
	/// <param name="nFile2">The file for tile2.</param>
	/// <returns>Returns a list of tiles between tile1 and tile2. If there are no tiles in between, or the tiles don't form a nice line then an empty list is returned.</returns>
	private function GetTilesBetweenTwoTiles($nRank1, $nFile1, $nRank2, $nFile2)
	{
		$this->nCalls++;

		$List = array();
		$nRank = abs($nRank1 - $nRank2);
		$nFile = abs($nFile1 - $nFile2);
		// If the horizontal, vertical or diagonal distance for rank or file is less than 2 then
		// the tiles are adjacent or the same.
		if ($nRank < 2 && $nFile < 2) return $List;
		//// If a rank or a file is not 0 then test if a diagonal line is formed. If not then return
		//// an empty list.
		//if ((nRanks == 0 || nFiles == 0) && nRanks == nFiles)
		// If a non valid diagonal line is created between two points, return an empty list.
		if(($nRank != 0 && $nFile != 0 && $nRank != $nFile))
			return $List;
		// Get the top rank and file and the bottom rank and file to make it easy to loop through.
		$nTopF = ($nFile1 > $nFile2) ? $nFile1 : $nFile2;
		$nBtmF = ($nFile1 > $nFile2) ? $nFile2 : $nFile1;
		$nTopR = ($nRank1 > $nRank2) ? $nRank1 : $nRank2;
		$nBtmR = ($nRank1 > $nRank2) ? $nRank2 : $nRank1;
		// Now collect all tiles between the two tiles.
		if ($nRank1 == $nRank2)
		{
			for ($nFile = $nBtmF + 1; $nFile < $nTopF; $nFile++)
				$List[] = $nFile + $nRank1 * 8;
		}
		else if ($nFile1 == $nFile2)
		{
			for ($nRank = $nBtmR + 1; $nRank < $nTopR; $nRank++)
				$List[] = $nFile1 + $nRank * 8;
		}
		else
		{
			$nRank = $nBtmR + 1;
			if ($nRank1 == $nTopR)
			{
				if ($nFile1 < $nFile2)    // Going up left.
				{
					for ($nFile = $nFile2 - 1; $nFile > $nFile1; $nFile--)
					{
						$List[] = $nFile + $nRank * 8;
						$nRank++;
					}
				}
				else   // Going up right
				{
					for ($nFile = $nFile2 + 1; $nFile < $nFile1; $nFile++)
					{
						$List[] = $nFile + $nRank * 8;
						$nRank++;
					}
				}
			}
			else
			{
				if ($nFile1 < $nFile2)   // Going up right.
				{
					for ($nFile = $nFile1 + 1; $nFile < $nFile2; $nFile++)
					{
						$List[] = $nFile + $nRank * 8;
						$nRank++;
					}
				}
				else  // Going up left.
				{
					for ($nFile = $nFile1 - 1; $nFile > $nFile2; $nFile--)
					{
						$List[] = $nFile + $nRank * 8;
						$nRank++;
					}
				}
			}
		}
		//else
		//{
		//    for (nFiles = nBtmF + 1; nFiles < nTopF; nFiles++)
		//    {
		//        for (nRank = nBtmR + 1; nRank < nTopR; nRank++)
		//            $List.Add(nFiles + nRank * 8);
		//    }
		//}
		return $List;
	}

	/// <summary>
	/// Tries to see if moving the king gets it out of check.
	/// </summary>
	/// <param name="nKingTile">The tile on which the king is.</param>
	/// <returns>Returns true if the king can move out of check.</returns>
	private function CanMoveOutOfCheck($nKingTile)
	{
		$this->nCalls++;
		$tiles;

		// Get all tiles the king can move to, temporarily move it and see if it is
		// still in check.
		$bitmap = $this->FindAttacksFromTile($this->floor[$nKingTile], $nKingTile % 8);
		if ($this->PlayerTurn == 0)
			//$bitmap ^= (bitmap & $this->n64WAll);
			$bitmap->_XOR(BitBoard::_AND_($bitmap, $this->n64WAll));
		else
			//$bitmap ^= (bitmap & $this->n64BAll);
			$bitmap->_XOR(BitBoard::_AND_($bitmap, $this->n64BAll));
		$tiles = $this->ChessUtils->GetTilesFromBitmap($bitmap);
		foreach ($tiles as $nTile)
		{
			$this->Board_RemovePiece($nTile % 8, $this->floor[$nTile]);
			$this->Board_MovePiece($nKingTile % 8, $this->floor[$nKingTile], $nTile % 8, $this->floor[$nTile]);
			if ($this->PlayerTurn == 0)
				$bitmap = $this->FindAttacksToTile($this->floor[$nTile], $nTile % 8)->_AND($this->n64BAll);
			else
				$bitmap = $this->FindAttacksToTile($this->floor[$nTile], $nTile % 8)->_AND($this->n64WAll);
			if ($bitmap->is_zero()) // No more checking moves.
			{
				// Move the king back as well as any captured piece
				// and return only check as the board status.
				$this->Board_MovePiece_($nTile % 8, $this->floor[$nTile], $nKingTile % 8, $this->floor[$nKingTile], PIECE_TYPE::KING + $this->PlayerTurn * 6);
				$this->Board_AddPiece($nTile % 8, $this->floor[$nTile], $this->Board[$this->floor[$nTile]][$nTile % 8]);
				return true;
			}
			// Move the king back and if a piece was captured then add it back.
			$this->Board_MovePiece_($nTile % 8, $this->floor[$nTile], $nKingTile % 8, $this->floor[$nKingTile], PIECE_TYPE::KING + $this->PlayerTurn * 6);
			$this->Board_AddPiece($nTile % 8, $this->floor[$nTile], $this->Board[$this->floor[$nTile]][$nTile % 8]);
		}
		return false;
	}

	/// <summary>
	/// Tests if a piece that has put a king into check can be captured.
	/// </summary>
	/// <param name="nKingTile">The tile the king in check is on.</param>
	/// <param name="nAttackTile">The tile the attacking piece is on.</param>
	/// <returns>Returns true if the attacking piece can be captured.</returns>
	private function CanCaptureCheckingPiece($nKingTile, $nAttackTile)
	{
		$this->nCalls++;
		$tiles = array();
		$checking_piece = $this->Board[$this->floor[$nAttackTile]][$nAttackTile % 8];
		// Get an array of pieces on tiles that can attack this tile.
		// For each make a temporary move to see what happens after taking
		// this piece and test if the king is still attacked by an opponent
		// piece. If not then we found a move to get out of check.
		$bitmap = $this->FindAttacksToTile($this->floor[$nAttackTile], $nAttackTile % 8);
		if ($this->PlayerTurn == 0)
			$tiles = $this->ChessUtils->GetTilesFromBitmap($bitmap->_AND($this->n64WAll));
		else
			$tiles = $this->ChessUtils->GetTilesFromBitmap($bitmap->_AND($this->n64BAll));
		// Remove the attacking piece temporarily.
		$this->Board_RemovePiece($nAttackTile % 8, $this->floor[$nAttackTile]);
		foreach ($tiles as $nTile)
		{
			$capture_piece = $this->Board[$this->floor[$nTile]][$nTile % 8];
			$this->Board_MovePiece($nTile % 8, $this->floor[$nTile], $nAttackTile % 8, $this->floor[$nAttackTile]);
			// When moving the king need to test if its new position is attacked.
			if ($nTile != $nKingTile)
			{
				if ($this->PlayerTurn == 0)
					$bitmap = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8)->_AND($this->n64BAll);
				else
					$bitmap = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8)->_AND($this->n64WAll);
			}
			else	// Moved king
			{
				if ($this->PlayerTurn == 0)
					$bitmap = $this->FindAttacksToTile($this->floor[$nAttackTile], $nAttackTile % 8)->_AND($this->n64BAll);
				else
					$bitmap = $this->FindAttacksToTile($this->floor[$nAttackTile], $nAttackTile % 8)->_AND($this->n64WAll);
			}
			if ($bitmap->is_zero()) // No more checking moves.
			{
				// Move the attacking piece back, put the taken piece back and 
				// return indicating that the checking piece can be taken.
				$this->Board_MovePiece_($nAttackTile % 8, $this->floor[$nAttackTile], $nTile % 8, $this->floor[$nTile], $capture_piece);
				$this->Board_AddPiece($nAttackTile % 8, $this->floor[$nAttackTile], $checking_piece);
				return true;
			}
			// Move piece back.
			$this->Board_MovePiece_($nAttackTile % 8, $this->floor[$nAttackTile], $nTile % 8, $this->floor[$nTile], $capture_piece);
		}
		// Add the taken piece back.
		$this->Board_AddPiece($nAttackTile % 8, $this->floor[$nAttackTile], $this->Board[$this->floor[$nAttackTile]][$nAttackTile % 8]);
		return false;
	}

	/// <summary>
	/// Tests if it is possible to block the given attacking piece from attacking the king.
	/// </summary>
	/// <param name="nKingTile">The king being attacked.</param>
	/// <param name="nAttackTile">The tile attacking the king.</param>
	/// <returns>Returns true if the attacking tile can be blocked.</returns>
	private function CanBlockCheckingPiece($nKingTile, $nAttackTile)
	{
		$this->nCalls++;

		// Get all tiles between the attacking piece and the king. Try to block one after the
		// other and test if making such a move gets us out of check. If a good move is found
		// return.
		$tiles = $this->GetTilesBetweenTwoTiles($this->floor[$nAttackTile], $nAttackTile % 8, $this->floor[$nKingTile], $nKingTile % 8);
		if (count($tiles) == 0) return false;
		foreach ($tiles as $nTile)
		{
			// See if any of the current player's pieces (except for the king) can move to this tile.
			if ($this->PlayerTurn == 0)
				//bitmap = FindAttacksToTile(nTile / 8, nTile % 8) & ($this->n64WAll ^ $this->n64WKing);
				$bitmap = $this->FindMovesToTile($this->floor[$nTile], $nTile % 8)->_AND(BitBoard::_XOR_($this->n64WAll, $this->n64WKing));
			else
				//bitmap = FindAttacksToTile(nTile / 8, nTile % 8) & ($this->n64BAll ^ $this->n64BKing);
				$bitmap = $this->FindMovesToTile($this->floor[$nTile], $nTile % 8)->_AND(BitBoard::_XOR_($this->n64BAll, $this->n64BKing));
			$tiles2 = $this->ChessUtils->GetTilesFromBitmap($bitmap);
			foreach ($tiles2 as $nSourceTile)
			{
				// Move the piece and if the king is no longer in check then all is good.
				$this->Board_MovePiece($nSourceTile % 8, $this->floor[$nSourceTile], $nTile % 8, $this->floor[$nTile]);
				if ($this->PlayerTurn == 0)
					$bitmap = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8)->_AND($this->n64BAll);
				else
					$bitmap = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8)->_AND($this->n64WAll);
				if ($bitmap->is_zero()) // No more checking moves.
				{
					// Undo temp move.
					$this->Board_MovePiece_($nTile % 8, $this->floor[$nTile], $nSourceTile % 8, $this->floor[$nSourceTile], $this->Board[$this->floor[$nSourceTile]][$nSourceTile % 8]);
					return true;
				}
				// Undo temp move.
				$this->Board_MovePiece_($nTile % 8, $this->floor[$nTile], $nSourceTile % 8, $this->floor[$nSourceTile], $this->Board[$this->floor[$nSourceTile]][$nSourceTile % 8]);
			}
		}
		return false;
	}

	/// <summary>
	/// Tests the board state to see if the king is in check or checkmate.
	/// </summary>
	/// <param name="status">A reference to a variable that will contain the updated board state which is either check, mate or normal.</param>
	private function TestForCheckAndMate(&$status)
	{
		$this->nCalls++;
		$nKingTile = 0;

		$status = CHESS_BOARD_STATUS::NORMAL;

		if ($this->PlayerTurn == 0)
		{
			// Get a bitmap storing all the tiles for opposition pieces that can attack the king.
			//if ($this->n64WKing == 0) return;
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nKingTile = $tmp[0];
			$bitmap = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
			$bitmap->_AND($this->n64BAll);
			// If the bitmap is 0 then the king is not under attack.
			if ($bitmap->is_zero()) return;
			$tiles = $this->ChessUtils->GetTilesFromBitmap($bitmap);

			if ($this->CanMoveOutOfCheck($nKingTile))
			{
				$status = CHESS_BOARD_STATUS::CHECK;
				return;
			}

			// See how many pieces are attacking. If more than 1 then we are stuffed since moving
			// around hasn't helped and we can only take one piece.
			if (count($tiles) > 1)
			{
				$status = CHESS_BOARD_STATUS::MATE;
				return;
			}
			// If the attacking piece is not a knight then see if it can be taken or blocked.
			if ($this->Board[$this->floor[$tiles[0]]][$tiles[0] % 8] != PIECE_TYPE::KNIGHT + 6)
			{
				if ($this->CanCaptureCheckingPiece($nKingTile, $tiles[0]))
				{
					$status = CHESS_BOARD_STATUS::CHECK;
					return;
				}
				if ($this->CanBlockCheckingPiece($nKingTile, $tiles[0]))
				{
					$status = CHESS_BOARD_STATUS::CHECK;
					return;
				}
			}
			else
			{
				// See if the knight can be taken.
				if ($this->CanCaptureCheckingPiece($nKingTile, $tiles[0]))
				{
					$status = CHESS_BOARD_STATUS::CHECK;
					return;
				}
			}
		}
		else
		{
			// Get a bitmap storing all the tiles for opposition pieces that can attack the king.
			//if ($this->n64BKing == 0) return;
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nKingTile = $tmp[0];
			$bitmap = $this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8);
			$bitmap->_AND($this->n64WAll);
			// If the bitmap is 0 then the king is not under attack.
			if ($bitmap->is_zero()) return;
			$tiles = $this->ChessUtils->GetTilesFromBitmap($bitmap);

			if ($this->CanMoveOutOfCheck($nKingTile))
			{
				$status = CHESS_BOARD_STATUS::CHECK;
				return;
			}

			// See how many pieces are attacking. If more than 1 then we are stuffed since moving
			// around hasn't helped and we can only take one piece.
			if (count($tiles) > 1)
			{
				$status = CHESS_BOARD_STATUS::MATE;
				return;
			}
			// If the attacking piece is a not a knight then see if it can be taken or blocked.
			if ($this->Board[$this->floor[$tiles[0]]][$tiles[0] % 8] != PIECE_TYPE::KNIGHT)
			{
				if ($this->CanCaptureCheckingPiece($nKingTile, $tiles[0]))
				{
					$status = CHESS_BOARD_STATUS::CHECK;
					return;
				}
				if ($this->CanBlockCheckingPiece($nKingTile, $tiles[0]))
				{
					$status = CHESS_BOARD_STATUS::CHECK;
					return;
				}
			}
			else
			{
				// See if the knight can be taken.
				if ($this->CanCaptureCheckingPiece($nKingTile, $tiles[0]))
				{
					$status = CHESS_BOARD_STATUS::CHECK;
					return;
				}
			}
		}
		// Everything failed so it must be checkmate.
		$status = CHESS_BOARD_STATUS::MATE;
		return;
	}

	/// <summary>
	/// Tests if the current side has any legal moves to make.
	/// </summary>
	/// <returns>Returns true if there are no more moves possible for the current side.</returns>
	private function IsSideInStalemate()
	{
		$this->nCalls++;

		if ($this->PlayerTurn == 0)
		{
			// Loop through all pieces for this player and try to move them. Once a 
			// valid move is found it indicates this state is not stalemate.
			//if ($this->n64WKing == 0) return true;
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64WKing);
			$nKingTile = $tmp[0];
			$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64WAll);
			foreach ($tiles as $tile)
			{
				$bitmap = $this->FindAttacksFromTile($this->floor[$tile], $tile % 8);
				$bitmap->_XOR(BitBoard::_AND_($this->n64WAll, $bitmap));
				$moves = $this->ChessUtils->GetTilesFromBitmap($bitmap);
				foreach ($moves as $move)
				{
					// Temporarily move the piece, change player turn and test if the king
					// is attacked as a result.
					$nAttackFile = $move % 8;
					$nAttackRank = $this->floor[$move];
					$this->Board_RemovePiece($nAttackFile, $nAttackRank);
					$this->Board_MovePiece($tile % 8, $this->floor[$tile], $nAttackFile, $nAttackRank);
					$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
					if ($tile != $nKingTile)
					{
						if ($this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8)->_AND($this->n64BAll)->is_zero())
						{
							$this->Board_MovePiece_($nAttackFile, $nAttackRank, $tile % 8, $this->floor[$tile], $this->Board[$this->floor[$tile]][$tile % 8]);
							$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
							$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
							return false;
						}
					}
					else  //King moved
					{
						if ($this->FindAttacksToTile($nAttackRank, $nAttackFile)->_AND($this->n64BAll)->is_zero())
						{
							$this->Board_MovePiece_($nAttackFile, $nAttackRank, $tile % 8, $this->floor[$tile], PIECE_TYPE::KING);
							$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
							$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
							return false;
						}
					}
					$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
					$this->Board_MovePiece_($nAttackFile, $nAttackRank, $tile % 8, $this->floor[$tile], $this->Board[$this->floor[$tile]][$tile % 8]);
					$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
				}
			}
		}
		else
		{
			//if ($this->n64BKing == 0) return true;
			$tmp = $this->ChessUtils->GetTilesFromBitmap($this->n64BKing);
			$nKingTile = $tmp[0];
			$tiles = $this->ChessUtils->GetTilesFromBitmap($this->n64BAll);
			foreach ($tiles as $tile)
			{
				$bitmap = $this->FindAttacksFromTile($this->floor[$tile], $tile % 8);
				$bitmap->_XOR(BitBoard::_AND_($this->n64BAll, $bitmap));
				$moves = $this->ChessUtils->GetTilesFromBitmap($bitmap);
				foreach ($moves as $move)
				{
					$nAttackFile = $move % 8;
					$nAttackRank = $this->floor[$move];
					$this->Board_RemovePiece($nAttackFile, $nAttackRank);
					$this->Board_MovePiece($tile % 8, $this->floor[$tile], $nAttackFile, $nAttackRank);
					$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
					if ($tile != $nKingTile)
					{
						if ($this->FindAttacksToTile($this->floor[$nKingTile], $nKingTile % 8)->_AND($this->n64WAll)->is_zero())
						{
							$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
							$this->Board_MovePiece_($nAttackFile, $nAttackRank, $tile % 8, $this->floor[$tile], $this->Board[$this->floor[$tile]][$tile % 8]);
							$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
							return false;
						}
					}
					else  //king moved
					{
						if ($this->FindAttacksToTile($nAttackRank, $nAttackFile)->_AND($this->n64WAll)->is_zero())
						{
							$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
							$this->Board_MovePiece_($nAttackFile, $nAttackRank, $tile % 8, $this->floor[$tile], PIECE_TYPE::KING + 6);
							$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
							return false;
						}
					}
					$this->PlayerTurn = $this->PlayerTurn == 0 ? 1 : 0;
					$this->Board_MovePiece_($nAttackFile, $nAttackRank, $tile % 8, $this->floor[$tile], $this->Board[$this->floor[$tile]][$tile % 8]);
					$this->Board_AddPiece($nAttackFile, $nAttackRank, $this->Board[$nAttackRank][$nAttackFile]);
				}
			}
		}
		// No allowable move found which means the players are in a stalemate.
		return true;
	}

	/// <summary>
	/// Updates the bitmaps for white and black that indicate what tiles each king can castle to.
	/// </summary>
	private function UpdateCastlingBitmaps()
	{
		$this->nCalls++;

		$this->n64WhiteCastle->set_value('0x0'); $this->n64BlackCastle->set_value('0x0');
		// If white king or black king are in check, then castling isn't possible;
		if ($this->PlayerTurn == 0 && $this->nStatus == CHESS_BOARD_STATUS::CHECK)
		{
			$this->n64WhiteCastle->set_value('0x0');
			return;
		}
		else if ($this->PlayerTurn == 1 && $this->nStatus == CHESS_BOARD_STATUS::CHECK)
		{
			$this->n64BlackCastle->set_value('0x0');
			return;
		}

		// NOTE: Limit to updating the castling bitboards to the next side to move because these bitboards
		// are only valid for the next move.
		
		// Need to check columns 2,3 and 5,6 for ranks 0 and 7 to see if they are empty and not attacked.
		if ($this->fCastleWQueenside)
		{
			if (BitBoard::_AND_($this->n64All, $this->n64WhiteCastleQFree)->is_zero())
			{
				$bitmap = $this->FindAttacksToTile(0, 2)->_AND($this->n64BAll);
				if ($bitmap->is_zero())
				{
					$bitmap = $this->FindAttacksToTile(0, 3)->_AND($this->n64BAll);
					if ($bitmap->is_zero()) $this->n64WhiteCastle->_OR($this->Tiles[2]);
				}
			}
		}
		if ($this->fCastleWKingside)
		{
			if (BitBoard::_AND_($this->n64All, $this->n64WhiteCastleKFree)->is_zero())
			{
				$bitmap = $this->FindAttacksToTile(0, 5)->_AND($this->n64BAll);
				if ($bitmap->is_zero())
				{
					$bitmap = $this->FindAttacksToTile(0, 6)->_AND($this->n64BAll);
					if ($bitmap->is_zero()) $this->n64WhiteCastle->_OR($this->Tiles[6]);
				}
			}
		}
		if ($this->fCastleBQueenside)
		{
			if (BitBoard::_AND_($this->n64All, $this->n64BlackCastleQFree)->is_zero())
			{
				$bitmap = $this->FindAttacksToTile(7, 2)->_AND($this->n64WAll);
				if ($bitmap->is_zero())
				{
					$bitmap = $this->FindAttacksToTile(7, 3)->_AND($this->n64WAll);
					if ($bitmap->is_zero()) $this->n64BlackCastle->_OR($this->Tiles[58]);
				}
			}
		}
		if ($this->fCastleBKingside)
		{
			if (BitBoard::_AND_($this->n64All, $this->n64BlackCastleKFree)->is_zero())
			{
				$bitmap = $this->FindAttacksToTile(7, 5)->_AND($this->n64WAll);
				if ($bitmap->is_zero())
				{
					$bitmap = $this->FindAttacksToTile(7, 6)->_AND($this->n64WAll);
					if ($bitmap->is_zero()) $this->n64BlackCastle->_OR($this->Tiles[62]);
				}
			}
		}

	}

	public function GetPiecesAttackingTile($nRank, $nFile)
	{
		return $this->ChessUtils->GetTilesFromBitmap($this->FindMovesToTile($nRank, $nFile));
		//return GetTilesFromBitmap(FindAttacksToTile(nRank, nFile));
	}
	
	
	public function debug_bitboards()
	{
		echo "<p style='clear: left'></p>";
	
		$this->debug_single_bitboard($this->n64All, "All");
		$this->debug_single_bitboard($this->n64WAll, "White");
		$this->debug_single_bitboard($this->n64BAll, "Black");
		$this->debug_board('$this->Board');
		
		echo "<p style='clear: left'>By Piece Type: White</p>";
		$this->debug_single_bitboard($this->n64WKing, "King");
		$this->debug_single_bitboard($this->n64WQueens, "Queens");
		$this->debug_single_bitboard($this->n64WBishops, "Bishops");
		$this->debug_single_bitboard($this->n64WKnights, "Knights");
		$this->debug_single_bitboard($this->n64WRooks, "Rooks");
		$this->debug_single_bitboard($this->n64WPawns, "Pawns");
		
		echo "<p style='clear: left'>By Piece Type: Black</p>";
		$this->debug_single_bitboard($this->n64BKing, "King");
		$this->debug_single_bitboard($this->n64BQueens, "Queens");
		$this->debug_single_bitboard($this->n64BBishops, "Bishops");
		$this->debug_single_bitboard($this->n64BKnights, "Knights");
		$this->debug_single_bitboard($this->n64BRooks, "Rooks");
		$this->debug_single_bitboard($this->n64BPawns, "Pawns");
		
		echo "<p style='clear: left'></p>";
	}
	
	private function debug_single_bitboard($board, $label = "")
	{
		echo '<div style="float: left; padding: 5px">';
		echo "$label";
		$board->print_nice();
		echo '</div>';
	}
	
	private function debug_board($label = "")
	{
		$html = "<div style='float: left; padding: 5px; font-family: monospace'>";
		$html .= "$label<br/><br/>";
		for($row = 7; $row > -1; $row--)
		{
			for($col = 0; $col < 8; $col++)
			{
				switch ($this->Board[$row][$col])
				{
					//black pieces
					case PIECE_TYPE::ROOK + 6:
						$html .= "<span style='color: black; background-color: #888'>R&nbsp;</span>";
						break;
					case PIECE_TYPE::KNIGHT + 6:
						$html .= "<span style='color: black; background-color: #888'>N&nbsp;</span>";
						break;
					case PIECE_TYPE::BISHOP + 6:
						$html .= "<span style='color: black; background-color: #888'>B&nbsp;</span>";
						break;
					case PIECE_TYPE::QUEEN + 6:
						$html .= "<span style='color: black; background-color: #888'>Q&nbsp;</span>";
						break;
					case PIECE_TYPE::KING + 6:
						$html .= "<span style='color: black; background-color: #888'>K&nbsp;</span>";
						break;
					case PIECE_TYPE::PAWN + 6:
						$html .= "<span style='color: black; background-color: #888'>P&nbsp;</span>";
						break;
					//white pieces
					case PIECE_TYPE::ROOK:
						$html .= "<span style='color: white; background-color: #888'>R&nbsp;</span>";
						break;
					case PIECE_TYPE::KNIGHT:
						$html .= "<span style='color: white; background-color: #888'>N&nbsp;</span>";
						break;
					case PIECE_TYPE::BISHOP:
						$html .= "<span style='color: white; background-color: #888'>B&nbsp;</span>";
						break;
					case PIECE_TYPE::QUEEN:
						$html .= "<span style='color: white; background-color: #888'>Q&nbsp;</span>";
						break;
					case PIECE_TYPE::KING:
						$html .= "<span style='color: white; background-color: #888'>K&nbsp;</span>";
						break;
					case PIECE_TYPE::PAWN:
						$html .= "<span style='color: white; background-color: #888'>P&nbsp;</span>";
						break;
					case PIECE_TYPE::UNDEFINED:
						$html .= "<span style='background-color: #888'>&nbsp;&nbsp;</span>";
				}
			}
			$html .= "<br/>";
		}
		$html .= "</div>";
		echo $html;
	}
	
	private function debug_castling()
	{
		echo '<div>CASTLING: ';
		echo ($this->fCastleBKingside ? 'B Kingside, ' : '');
		echo ($this->fCastleBQueenside ? 'B Queenside, ' : '');
		echo ($this->fCastleWKingside ? 'W Kingside, ' : '');
		echo ($this->fCastleWQueenside ? 'W Queenside ' : '');
		echo '</div>'; 
	}
	
	public function debug_en_passant()
	{	
		echo "<div>En Passant Square: " . ($this->nEnPassantSquare > 0 ? $this->nEnPassantSquare : "None")  . "</div>";
	}
	
	public function debug_movelist()
	{
		echo "<pre>"; var_dump($this->MoveList); echo "</pre>";
	}
	
	// Generates php code to create attack bitmaps using hard coded values.
	public function get_attack_bitmaps_as_php_code()
	{
		$code = "";
		$i = 0;
		foreach($this->Tiles as $attack)
		{
			$code .= "\$this->Tiles[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->KingAttacks as $attack)
		{
			$code .= "\$this->KingAttacks[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->KnightAttacks as $attack)
		{
			$code .= "\$this->KnightAttacks[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->WPawnAttacks as $attack)
		{
			$code .= "\$this->WPawnAttacks[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->WPawnCaptures as $attack)
		{
			$code .= "\$this->WPawnCaptures[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->BPawnAttacks as $attack)
		{
			$code .= "\$this->BPawnAttacks[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->BPawnCaptures as $attack)
		{
			$code .= "\$this->BPawnCaptures[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Plus1 as $attack)
		{
			$code .= "\$this->Plus1[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Plus7 as $attack)
		{
			$code .= "\$this->Plus7[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Plus8 as $attack)
		{
			$code .= "\$this->Plus8[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Plus9 as $attack)
		{
			$code .= "\$this->Plus9[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Minus1 as $attack)
		{
			$code .= "\$this->Minus1[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Minus7 as $attack)
		{
			$code .= "\$this->Minus7[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Minus8 as $attack)
		{
			$code .= "\$this->Minus8[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$i = 0;
		foreach($this->Minus9 as $attack)
		{
			$code .= "\$this->Minus9[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		$code .= "<br/>";
		$code .= "\$this->n64WhiteCastleKFree = " . $this->get_bitmap_as_php_code($this->n64WhiteCastleKFree);
		$code .= "<br/>";
		$code .= "\$this->n64WhiteCastleQFree = " . $this->get_bitmap_as_php_code($this->n64WhiteCastleQFree);
		$code .= "<br/>";
		$code .= "\$this->n64BlackCastleKFree = " . $this->get_bitmap_as_php_code($this->n64BlackCastleKFree);
		$code .= "<br/>";
		$code .= "\$this->n64BlackCastleQFree = " . $this->get_bitmap_as_php_code($this->n64BlackCastleQFree);
		$code .= "<br/>";
		$i = 0;
		foreach($this->Ranks as $attack)
		{
			$code .= "\$this->Ranks[$i] = " . $this->get_bitmap_as_php_code($attack);
			$i++;
		}
		return $code;
	}
	
	private function get_bitmap_as_php_code($bm)
	{
		$code = "\$bb = new BitBoard(); ";
		if($bm->num1 > 0)
			$code .= "\$bb->num1 = " . $bm->num1 . '; ';
		if($bm->num2 > 0)
			$code .= "\$bb->num2 = " . $bm->num2 . '; ';
		if($bm->num3 > 0)
			$code .= "\$bb->num3 = " . $bm->num3 . '; ';
		if($bm->num4 > 0)
			$code .= "\$bb->num4 = " . $bm->num4 . ';';
		$code .= '<br/>';
		return $code;
	}
}

?>