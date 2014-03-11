
// Chess constants
var ChessConst = {
	// Side the player is in control of. 'none' means viewing a game. 'both' means controlling both sides. Used
	// for example when editing a game or for tutorials.
	SIDE: { none: -1, white: 0, black: 1, both: 2 },
	
	// Indicates what piece is on a square. 13 types: 6 white/black piece types and 'none' for empty squares.
	PIECE_TYPE: { none: 0, wKing: 1, wQueen: 2, wBishop: 3, wKnight: 4, wRook: 5, wPawn: 6, bKing: 7, bQueen: 8, bBishop: 9, bKnight: 10, bRook: 11, bPawn: 12 },
	
	// Inidicates what piece is on a square ignoring if they are white or black. 6 piece types and 'none' for empty squares.
	GENERAL_PIECE_TYPE: { none: 0, king: 1, queen: 2, bishop: 3, knight: 4, rook: 5, pawn: 6 },
	
	// Defines chess board states
	
	// State of a chess game.
	CHESS_BOARD_STATUS: { NORMAL: 0, CHECK: 1, MATE: 2, FIFTY: 3, STALEMATE: 4 },
	
	// Result of a game that has finished. 'UNKNOWN' used for games in progress.
	CHESS_GAME_RESULT: { UNKNOWN: 0, WHITEWIN: 1, BLACKWIN: 2, DRAW: 3 },
	
	// Indicates which side of a game has requested a draw. Can be either no side, one or the other or both have requested
	// a draw. When both have requested a draw, the game is ended in a draw.
	//CHESS_GAME_DRAW_STATUS = {NONE: 0, SELF: 1, OPPONENT: 2, DRAW: 3}
	CHESS_GAME_DRAW_STATUS: {NONE: 0, WHITE: 1, BLACK: 2, DRAW: 3}
}


// Common helper functions
var ChessHelper = {
	// Returns array containing the tile coords of chess pieces. Tile coords are 0 based.
	get_pieces_from_FEN: function(fen)
	{
		var spaces = {'1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8};
		var parts = fen.split(' ');
		var rows = parts[0].split('/');
		var pieces = [];
		var x, y = 7;		// current tile coords while looping through fen.
		for(var r = 0; r < rows.length; r++)
		{
			x = 0;
			var row = rows[r];
			for(i = 0; i < row.length; i++)
			{
				var chr = row[i];
				switch(chr) {
					case 'k':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.bKing});
						x++;
						break;
					case 'q':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.bQueen});
						x++;
						break;
					case 'b':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.bBishop});
						x++;
						break;
					case 'n':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.bKnight});
						x++;
						break;
					case 'r':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.bRook});
						x++;
						break;
					case 'p':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.bPawn});
						x++;
						break;
					case 'K':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.wKing});
						x++;
						break;
					case 'Q':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.wQueen});
						x++;
						break;
					case 'B':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.wBishop});
						x++;
						break;
					case 'N':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.wKnight});
						x++;
						break;
					case 'R':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.wRook});
						x++;
						break;
					case 'P':
						pieces.push({x: x, y: y, type: ChessConst.PIECE_TYPE.wPawn});
						x++;
						break;
					default:
						if(spaces[chr] !== undefined)
							x += spaces[chr];
				}
			}
			y--;
		}
		return pieces;
	},
	
	get_side_from_piece: function(type)
	{
	//console.log(type <= PIECE_TYPE.wPawn ? SIDE.white : SIDE.black);
		return type <= ChessConst.PIECE_TYPE.wPawn ? ChessConst.SIDE.white : ChessConst.SIDE.black;
	},
	
	get_general_type_of_piece: function(type)
	{
		return (type <= ChessConst.PIECE_TYPE.wPawn ? type : type - 6);
	},
	
	// Converts a tile index to a tile coord string (0 = 'a1', 63 = 'h8').
	index_to_coord: function(index)
	{
		var files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
		return files[index % 8] + (Math.floor(index / 8) + 1);
	},
	
	// Converts a tile coord string to a tile index ('a1' = 0, 'h8' = 63).
	coord_to_index: function(index)
	{
		var files = {a: 0, b: 1, c: 2, d: 3, e: 4, f: 5, g: 6, h: 7};
		return (index[1] - 1) * 8 + files[index[0]];
	}
	
}