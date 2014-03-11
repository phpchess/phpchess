<?php

class PIECE_TYPE
{
	const UNDEFINED = 0;
	const KING = 1;
	const QUEEN = 2;
	const BISHOP = 3;
	const KNIGHT = 4;
	const ROOK = 5;
	const PAWN = 6;
}

// Classifications for chess moves
class MOVE_TYPE
{
	const NORMAL = 0;
	const CAPTURED = 1;
	const ENPASSANT = 2;
	const CASTLEK = 3;
	const CASTLEQ = 4;
}

// Defines game results
class CHESS_GAME_RESULT
{
	const UNKOWN = 0;
	const WHITEWIN = 1;
	const BLACKWIN = 2;
	const DRAW = 3;
}

// Defines reasons for the game result.
class CHESS_GAME_RESULT_REASON
{
	const CHECKMATE = 0;
	const RESIGNED = 1;
	const OUTOFTIME = 2;
	const DRAWAGREE = 3;
	const DRAW50 = 4;
	const DRAW_REPETITION = 5;
}

// Defines chess board states
class CHESS_BOARD_STATUS
{
	const NORMAL = 0;
	const CHECK = 1;
	const MATE = 2;
	const FIFTY = 3;
	const STALEMATE = 4;
}

class PLAYER_SIDE
{
	const WHITE = 0;
	const BLACK = 1;
}

class STRING_UTILS
{
	static function last_char($str)
	{
		if(strlen($str) == 0) return "";
		return $str[strlen($str) - 1];
	}
	
	static function in_string($str, $search)
	{
		return strstr($str, $search) !== FALSE ? TRUE : FALSE;
	}
}

class ChessBoardUtilities
{
	/// <summary>
	/// Converts a move (start-end tile coords) into Long Algebraic notation. BUG: This doesn't know about captures!
	/// </summary>
	/// <param name="nStartTile">Start tile.</param>
	/// <param name="nEndTile">End tile.</param>
	/// <param name="piece">The piece being moved.</param>
	/// <param name="promotionType">The piece type being promoted to if there is a promotion. Otherwise use the 'UNDEFINED' enum value.</param>
	/// <returns></returns>
	public function ConvertMoveToLongNotation($nStartTile, $nEndTile, $piece, $promotionType)
	{
		$type = ""; $promote = "";
		if ($piece == PIECE_TYPE::BISHOP || $piece == PIECE_TYPE::BISHOP + 6)
			$type = "B";
		if ($piece == PIECE_TYPE::KING || $piece == PIECE_TYPE::KING + 6)
			$type = "K";
		if ($piece == PIECE_TYPE::KNIGHT || $piece == PIECE_TYPE::KNIGHT + 6)
			$type = "N";
		if ($piece == PIECE_TYPE::ROOK || $piece == PIECE_TYPE::ROOK + 6)
			$type = "R";
		if ($piece == PIECE_TYPE::QUEEN || $piece == PIECE_TYPE::QUEEN + 6)
			$type = "Q";
		if ($promotionType == PIECE_TYPE::BISHOP) $promote .= "=B";
		if ($promotionType == PIECE_TYPE::QUEEN) $promote .= "=Q";
		if ($promotionType == PIECE_TYPE::KNIGHT) $promote .= "=N";
		if ($promotionType == PIECE_TYPE::ROOK) $promote .= "=R";
		return $type . $this->ConvertIntegerTileToAlgebraicNotation($nStartTile) . $this->ConvertIntegerTileToAlgebraicNotation($nEndTile) . $promote;
	}

	/// <summary>
	/// Converts a tile to algebraic notation (letter for the rank, and number for the file).
	/// </summary>
	/// <param name="nTile">The tile value to convert.</param>
	/// <returns>String representing the tile in algebraic notation. If tile was invalid, an empty string is returned.</returns>
	public function ConvertIntegerTileToAlgebraicNotation($nTile)
	{
		if($nTile === '') return '';
		$nFile = $nTile % 8;
		$nRank = floor($nTile / 8);
		$tile = "";

		if ($nFile == 0) $tile = "a";
		if ($nFile == 1) $tile = "b";
		if ($nFile == 2) $tile = "c";
		if ($nFile == 3) $tile = "d";
		if ($nFile == 4) $tile = "e";
		if ($nFile == 5) $tile = "f";
		if ($nFile == 6) $tile = "g";
		if ($nFile == 7) $tile = "h";
		if ($nRank == 0) $tile .= "1";
		if ($nRank == 1) $tile .= "2";
		if ($nRank == 2) $tile .= "3";
		if ($nRank == 3) $tile .= "4";
		if ($nRank == 4) $tile .= "5";
		if ($nRank == 5) $tile .= "6";
		if ($nRank == 6) $tile .= "7";
		if ($nRank == 7) $tile .= "8";

		return $tile;
	}


	/// <summary>
	/// Converts algebraic tile notation to an integer value.
	/// </summary>
	/// <param name="tile">The tile in algebraic notation.</param>
	/// <returns>Integer value representing the tile. If the tile isn't valid then -1 is returned.</returns>
	public function ConvertAlgebraicNotationTileToInteger($tile)
	{
		if(strlen($tile) != 2) return -1;
		$nTile = -1;
		if ($tile[0] == "a") $nTile = 0;
		else if ($tile[0] == "b") $nTile = 1;
		else if ($tile[0] == "c") $nTile = 2;
		else if ($tile[0] == "d") $nTile = 3;
		else if ($tile[0] == "e") $nTile = 4;
		else if ($tile[0] == "f") $nTile = 5;
		else if ($tile[0] == "g") $nTile = 6;
		else if ($tile[0] == "h") $nTile = 7;
		else return -1;
		if ($tile[1] == "1") $nTile += 0;
		else if ($tile[1] == "2") $nTile += 8;
		else if ($tile[1] == "3") $nTile += 16;
		else if ($tile[1] == "4") $nTile += 24;
		else if ($tile[1] == "5") $nTile += 32;
		else if ($tile[1] == "6") $nTile += 40;
		else if ($tile[1] == "7") $nTile += 48;
		else if ($tile[1] == "8") $nTile += 56;
		else return -1;
		return $nTile;
	}

	/// <summary>
	/// Makes sure a fen string has the proper structure and legal values for its different fields.
	/// </summary>
	/// <param name="=FEN">The FEN string to validate.</param>
	/// <param name="errors">Reference to a string to contain any error messages.</param>
	/// <returns>Returns true if the FEN is valid in terms of its structure and field values.</returns>
	public function ValidateFENStringStructureAndValues($FEN, &$errors)
	{
		$fields;      	// Stores each field (space delimited item).
		$row;        	// Stores each row of the FEN.
		$nWKingCount = 0; $nBKingCount = 0;   // The number of kings found.
		$errors = "";

		if ($FEN == null) return false;

		// Makes sure all groups and board ranks are available.
		$fields = preg_split('/ /', $FEN);
		if (count($fields) != 6)
		{
			$errors = "The number of fields in the FEN is not 6.";
			return false;
		}
		// If the board setup field ends with a "/" (PHPChess returned fen) then remove it.
		if (STRING_UTILS::last_char($fields[0]) == "/")
			$fields[0] = substr($fields[0], 0, count($fields[0]) - 2);
		$row = preg_split('/\//', $fields[0]);
		if (count($row) != 8)
		{
			$errors = "The number of ranks in the board setup field is not 8.";
			return false;
		}

		// Now check the values for each group are acceptable.

		// First check that the board characters are acceptable.
		for ($i = 0; $i < strlen($fields[0]); $i++)
		{
			if (!($fields[0][$i] == 'K' || $fields[0][$i] == 'Q' || $fields[0][$i] == 'R' || $fields[0][$i] == 'B'
				|| $fields[0][$i] == 'N' || $fields[0][$i] == 'P' || $fields[0][$i] == 'k' || $fields[0][$i] == 'q'
				|| $fields[0][$i] == 'r' || $fields[0][$i] == 'b' || $fields[0][$i] == 'n' || $fields[0][$i] == 'p'
				|| $fields[0][$i] == '/' || $fields[0][$i] == '1' || $fields[0][$i] == '2' || $fields[0][$i] == '3'
				|| $fields[0][$i] == '4' || $fields[0][$i] == '5' || $fields[0][$i] == '6' || $fields[0][$i] == '7'
				|| $fields[0][$i] == '8'))
			{
				$errors = "The FEN string contains an invalid character: \n" + $fields[0][$i];
				return false;
			}
			if ($fields[0][$i] == 'K') $nWKingCount++;
			if ($fields[0][$i] == 'k') $nBKingCount++;
		}

		// Make sure there is one white and one black king
		if ($nWKingCount == 0)
			$errors .= "No White king.\n";
		if ($nBKingCount == 0)
			$errors .= "No Black king.\n";
		if ($nWKingCount > 1)
			$errors .= "There can only be one king for black.\n";
		if ($nBKingCount > 1)
			$errors .= "There can only be one king for black.\n";

		// Check if the player turn is either w or b.
		if (!($fields[1] == "w" || $fields[1] == "b"))
			$errors .= "Player turn must be either 'w' or 'b'.\n";

		// Check the castling characters are valid. There must be 1 to 4 characters.
		// If a '-' then no other character should be there. If no '-' then only k,K,q and Q allowed.
		if (strlen($fields[2]) < 1 || strlen($fields[2]) > 4)
			$errors .= "The castling field of the FEN is invalid.\n";
		if (strstr($fields[2], "-") && strlen($fields[2]) != 1)
			$errors .= "Cannot have a '-' in the castling field with other characters.\n";
		if (strlen($fields[2]) > 1)
		{
			for ($i = 0; $i < strlen($fields[2]); $i++)
			{
				if (!($fields[2][$i] == 'k' || $fields[2][$i] == 'K' || $fields[2][$i] == 'Q' || $fields[2][$i] == 'q'))
					$errors .= "The castling field contained an invalid character.\n";
			}
		}

		// The en-passant field can only be '-' or a valid tile (a1 through to h8)
		if ($fields[3] != "-")
		{
			if ($this->ConvertAlgebraicNotationTileToInteger($fields[3]) == -1)
				$errors .= "The en passant field must contain a valid tile reference.\n";
		}

		// The 50 move field must be non negative.
		if ($this->IsStringNonNegativeNumber($fields[4]) == false)
			$errors .= "The 50 move (half move count) field must be non negative.\n";

		// The full move number must be non negative.
		if ($this->IsStringNonNegativeNumber($fields[5]) == false)
			$errors .= "The full move field must be non negative\n";

		if ($errors == "")
			return true;
		else
			return false;
	}

	/// <summary>
	/// Checks if a string contains a non negative number (of type UInt32)
	/// </summary>
	/// <param name="sz">The string to test.</param>
	/// <returns>Resturns true if the number if non negative. Else false is returned.</returns>
	private function IsStringNonNegativeNumber($str)
	{
		$num = (int)$str;
		return $num >= 0 ? true : false;
	}


	# region Bitboard Related Functions

	/// <summary>
	/// Find the position of the first bit set to 1 in a 64bit number, going from the LSB to the MSB
	/// </summary>
	/// <param name="number">The 64bit number to check.</param>
	/// <returns>Returns the position of the first bit set to 1. If no 1bits then 63 is returned.</returns>
	public function FindFirstOneBit($number)
	{
		if ((0x1 & $number) != 0) return 0;
		if ((0x2 & $number) != 0) return 1;
		if ((0x4 & $number) != 0) return 2;
		if ((0x8 & $number) != 0) return 3;
		if ((0x10 & $number) != 0) return 4;
		if ((0x20 & $number) != 0) return 5;
		if ((0x40 & $number) != 0) return 6;
		if ((0x80 & $number) != 0) return 7;
		if ((0x100 & $number) != 0) return 8;
		if ((0x200 & $number) != 0) return 9;
		if ((0x400 & $number) != 0) return 10;
		if ((0x800 & $number) != 0) return 11;
		if ((0x1000 & $number) != 0) return 12;
		if ((0x2000 & $number) != 0) return 13;
		if ((0x4000 & $number) != 0) return 14;
		if ((0x8000 & $number) != 0) return 15;
		if ((0x10000 & $number) != 0) return 16;
		if ((0x20000 & $number) != 0) return 17;
		if ((0x40000 & $number) != 0) return 18;
		if ((0x80000 & $number) != 0) return 19;
		if ((0x100000 & $number) != 0) return 20;
		if ((0x200000 & $number) != 0) return 21;
		if ((0x400000 & $number) != 0) return 22;
		if ((0x800000 & $number) != 0) return 23;
		if ((0x1000000 & $number) != 0) return 24;
		if ((0x2000000 & $number) != 0) return 25;
		if ((0x4000000 & $number) != 0) return 26;
		if ((0x8000000 & $number) != 0) return 27;
		if ((0x10000000 & $number) != 0) return 28;
		if ((0x20000000 & $number) != 0) return 29;
		if ((0x40000000 & $number) != 0) return 30;
		if ((0x80000000 & $number) != 0) return 31;
		if ((0x100000000 & $number) != 0) return 32;
		if ((0x200000000 & $number) != 0) return 33;
		if ((0x400000000 & $number) != 0) return 34;
		if ((0x800000000 & $number) != 0) return 35;
		if ((0x1000000000 & $number) != 0) return 36;
		if ((0x2000000000 & $number) != 0) return 37;
		if ((0x4000000000 & $number) != 0) return 38;
		if ((0x8000000000 & $number) != 0) return 39;
		if ((0x10000000000 & $number) != 0) return 40;
		if ((0x20000000000 & $number) != 0) return 41;
		if ((0x40000000000 & $number) != 0) return 42;
		if ((0x80000000000 & $number) != 0) return 43;
		if ((0x100000000000 & $number) != 0) return 44;
		if ((0x200000000000 & $number) != 0) return 45;
		if ((0x400000000000 & $number) != 0) return 46;
		if ((0x800000000000 & $number) != 0) return 47;
		if ((0x1000000000000 & $number) != 0) return 48;
		if ((0x2000000000000 & $number) != 0) return 49;
		if ((0x4000000000000 & $number) != 0) return 50;
		if ((0x8000000000000 & $number) != 0) return 51;
		if ((0x10000000000000 & $number) != 0) return 52;
		if ((0x20000000000000 & $number) != 0) return 53;
		if ((0x40000000000000 & $number) != 0) return 54;
		if ((0x80000000000000 & $number) != 0) return 55;
		if ((0x100000000000000 & $number) != 0) return 56;
		if ((0x200000000000000 & $number) != 0) return 57;
		if ((0x400000000000000 & $number) != 0) return 58;
		if ((0x800000000000000 & $number) != 0) return 59;
		if ((0x1000000000000000 & $number) != 0) return 60;
		if ((0x2000000000000000 & $number) != 0) return 61;
		if ((0x4000000000000000 & $number) != 0) return 62;
		return 63;
	}

	/// <summary>
	/// Find the position of the last 1 bit in a 64bit number, going from the LSB to the MSB.
	/// </summary>
	/// <param name="number">The 64bit number to check</param>
	/// <returns>Returns the location of the last bit set to 1. If no bits are one then 0 is returned.</returns>
	public function FindLastOneBit($number)
	{
		if ((0x8000000000000000 & $number) != 0) return 63;
		if ((0x4000000000000000 & $number) != 0) return 62;
		if ((0x2000000000000000 & $number) != 0) return 61;
		if ((0x1000000000000000 & $number) != 0) return 60;
		if ((0x800000000000000 & $number) != 0) return 59;
		if ((0x400000000000000 & $number) != 0) return 58;
		if ((0x200000000000000 & $number) != 0) return 57;
		if ((0x100000000000000 & $number) != 0) return 56;
		if ((0x80000000000000 & $number) != 0) return 55;
		if ((0x40000000000000 & $number) != 0) return 54;
		if ((0x20000000000000 & $number) != 0) return 53;
		if ((0x10000000000000 & $number) != 0) return 52;
		if ((0x8000000000000 & $number) != 0) return 51;
		if ((0x4000000000000 & $number) != 0) return 50;
		if ((0x2000000000000 & $number) != 0) return 49;
		if ((0x1000000000000 & $number) != 0) return 48;
		if ((0x800000000000 & $number) != 0) return 47;
		if ((0x400000000000 & $number) != 0) return 46;
		if ((0x200000000000 & $number) != 0) return 45;
		if ((0x100000000000 & $number) != 0) return 44;
		if ((0x80000000000 & $number) != 0) return 43;
		if ((0x40000000000 & $number) != 0) return 42;
		if ((0x20000000000 & $number) != 0) return 41;
		if ((0x10000000000 & $number) != 0) return 40;
		if ((0x8000000000 & $number) != 0) return 39;
		if ((0x4000000000 & $number) != 0) return 38;
		if ((0x2000000000 & $number) != 0) return 37;
		if ((0x1000000000 & $number) != 0) return 36;
		if ((0x800000000 & $number) != 0) return 35;
		if ((0x400000000 & $number) != 0) return 34;
		if ((0x200000000 & $number) != 0) return 33;
		if ((0x100000000 & $number) != 0) return 32;
		if ((0x80000000 & $number) != 0) return 31;
		if ((0x40000000 & $number) != 0) return 30;
		if ((0x20000000 & $number) != 0) return 29;
		if ((0x10000000 & $number) != 0) return 28;
		if ((0x8000000 & $number) != 0) return 27;
		if ((0x4000000 & $number) != 0) return 26;
		if ((0x2000000 & $number) != 0) return 25;
		if ((0x1000000 & $number) != 0) return 24;
		if ((0x800000 & $number) != 0) return 23;
		if ((0x400000 & $number) != 0) return 22;
		if ((0x200000 & $number) != 0) return 21;
		if ((0x100000 & $number) != 0) return 20;
		if ((0x80000 & $number) != 0) return 19;
		if ((0x40000 & $number) != 0) return 18;
		if ((0x20000 & $number) != 0) return 17;
		if ((0x10000 & $number) != 0) return 16;
		if ((0x8000 & $number) != 0) return 15;
		if ((0x4000 & $number) != 0) return 14;
		if ((0x2000 & $number) != 0) return 13;
		if ((0x1000 & $number) != 0) return 12;
		if ((0x800 & $number) != 0) return 11;
		if ((0x400 & $number) != 0) return 10;
		if ((0x200 & $number) != 0) return 9;
		if ((0x100 & $number) != 0) return 8;
		if ((0x80 & $number) != 0) return 7;
		if ((0x40 & $number) != 0) return 6;
		if ((0x20 & $number) != 0) return 5;
		if ((0x10 & $number) != 0) return 4;
		if ((0x8 & $number) != 0) return 3;
		if ((0x4 & $number) != 0) return 2;
		if ((0x2 & $number) != 0) return 1;
		return 0;
	}

	/// <summary>
	/// Processes a bitmap and for each 1 bit found, converts that to a tile number and then returns all tile numbers found.
	/// </summary>
	/// <param name="number">The bitmap representing some aspect of the board.</param>
	/// <returns>Returns an array of tile indices.</returns>
	public function GetTilesFromBitmap($number)
	{
		return $number->get_pos_of_all_one_bits();
		
		$tiles = array();
		if ((0x1 & $number) != 0) $tiles[] = 0;
		if ((0x2 & $number) != 0) $tiles[] = 1;
		if ((0x4 & $number) != 0) $tiles[] = 2;
		if ((0x8 & $number) != 0) $tiles[] = 3;
		if ((0x10 & $number) != 0) $tiles[] = 4;
		if ((0x20 & $number) != 0) $tiles[] = 5;
		if ((0x40 & $number) != 0) $tiles[] = 6;
		if ((0x80 & $number) != 0) $tiles[] = 7;
		if ((0x100 & $number) != 0) $tiles[] = 8;
		if ((0x200 & $number) != 0) $tiles[] = 9;
		if ((0x400 & $number) != 0) $tiles[] = 10;
		if ((0x800 & $number) != 0) $tiles[] = 11;
		if ((0x1000 & $number) != 0) $tiles[] = 12;
		if ((0x2000 & $number) != 0) $tiles[] = 13;
		if ((0x4000 & $number) != 0) $tiles[] = 14;
		if ((0x8000 & $number) != 0) $tiles[] = 15;
		if ((0x10000 & $number) != 0) $tiles[] = 16;
		if ((0x20000 & $number) != 0) $tiles[] = 17;
		if ((0x40000 & $number) != 0) $tiles[] = 18;
		if ((0x80000 & $number) != 0) $tiles[] = 19;
		if ((0x100000 & $number) != 0) $tiles[] = 20;
		if ((0x200000 & $number) != 0) $tiles[] = 21;
		if ((0x400000 & $number) != 0) $tiles[] = 22;
		if ((0x800000 & $number) != 0) $tiles[] = 23;
		if ((0x1000000 & $number) != 0) $tiles[] = 24;
		if ((0x2000000 & $number) != 0) $tiles[] = 25;
		if ((0x4000000 & $number) != 0) $tiles[] = 26;
		if ((0x8000000 & $number) != 0) $tiles[] = 27;
		if ((0x10000000 & $number) != 0) $tiles[] = 28;
		if ((0x20000000 & $number) != 0) $tiles[] = 29;
		if ((0x40000000 & $number) != 0) $tiles[] = 30;
		if ((0x80000000 & $number) != 0) $tiles[] = 31;
		if ((0x100000000 & $number) != 0) $tiles[] = 32;
		if ((0x200000000 & $number) != 0) $tiles[] = 33;
		if ((0x400000000 & $number) != 0) $tiles[] = 34;
		if ((0x800000000 & $number) != 0) $tiles[] = 35;
		if ((0x1000000000 & $number) != 0) $tiles[] = 36;
		if ((0x2000000000 & $number) != 0) $tiles[] = 37;
		if ((0x4000000000 & $number) != 0) $tiles[] = 38;
		if ((0x8000000000 & $number) != 0) $tiles[] = 39;
		if ((0x10000000000 & $number) != 0) $tiles[] = 40;
		if ((0x20000000000 & $number) != 0) $tiles[] = 41;
		if ((0x40000000000 & $number) != 0) $tiles[] = 42;
		if ((0x80000000000 & $number) != 0) $tiles[] = 43;
		if ((0x100000000000 & $number) != 0) $tiles[] = 44;
		if ((0x200000000000 & $number) != 0) $tiles[] = 45;
		if ((0x400000000000 & $number) != 0) $tiles[] = 46;
		if ((0x800000000000 & $number) != 0) $tiles[] = 47;
		if ((0x1000000000000 & $number) != 0) $tiles[] = 48;
		if ((0x2000000000000 & $number) != 0) $tiles[] = 49;
		if ((0x4000000000000 & $number) != 0) $tiles[] = 50;
		if ((0x8000000000000 & $number) != 0) $tiles[] = 51;
		if ((0x10000000000000 & $number) != 0) $tiles[] = 52;
		if ((0x20000000000000 & $number) != 0) $tiles[] = 53;
		if ((0x40000000000000 & $number) != 0) $tiles[] = 54;
		if ((0x80000000000000 & $number) != 0) $tiles[] = 55;
		if ((0x100000000000000 & $number) != 0) $tiles[] = 56;
		if ((0x200000000000000 & $number) != 0) $tiles[] = 57;
		if ((0x400000000000000 & $number) != 0) $tiles[] = 58;
		if ((0x800000000000000 & $number) != 0) $tiles[] = 59;
		if ((0x1000000000000000 & $number) != 0) $tiles[] = 60;
		if ((0x2000000000000000 & $number) != 0) $tiles[] = 61;
		if ((0x4000000000000000 & $number) != 0) $tiles[] = 62;
		if ((0x8000000000000000 & $number) != 0) $tiles[] = 63;

		return $tiles;
	}

	#endregion
}

?>