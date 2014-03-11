var SIDE = {none: -1, white: 0, black: 1};
var PIECE_TYPE = {none: 0, wKing: 1, wQueen: 2, wBishop: 3, wKnight: 4, wRook: 5, wPawn: 6, bKing: 7, bQueen: 8, bBishop: 9, bKnight: 10, bRook: 11, bPawn: 12};
var GENERAL_PIECE_TYPE = {none: 0, king: 1, queen: 2, bishop: 3, knight: 4, rook: 5, pawn: 6};
// Defines chess board states
var CHESS_BOARD_STATUS = {NORMAL: 0, CHECK: 1, MATE: 2, FIFTY: 3, STALEMATE: 4};
var CHESS_GAME_RESULT = {UNKNOWN: 0, WHITEWIN: 1, BLACKWIN: 2, DRAW: 3};
var CHESS_GAME_DRAW_STATUS = {NONE: 0, SELF: 1, OPPONENT: 2, DRAW: 3};

function ChessBoard()
{
	var self = this;
	this.els = {
		container: undefined,
		border_top: undefined,
		border_bottom: undefined,
		border_left: undefined,
		border_right: undefined,
		main: undefined,
		from_tile_marker: undefined,
		to_tile_marker: undefined,
		highlight_tile: undefined,
		start_tile: undefined,
		board_mask: undefined,
		board_mask2: undefined,
		promotion_dialog: undefined
	};
	
	this.tile_width = 0;
	this.border_width = 0;
	this.side;
	this.turn;
	this.colours = {btile: '#666', wtile: '#FFF', highlighted_tile: '#0F0', prv_move: '#F60', stile: '#0F0'};
	this.set = {location: './modules/RealTimeInterface/img_chess/', white_files: ['wkw.gif', 'wqw.gif', 'wbw.gif', 'wnw.gif', 'wrw.gif', 'wpw.gif'], black_files: ['bkw.gif', 'bqw.gif', 'bbw.gif', 'bnw.gif', 'brw.gif', 'bpw.gif'] };
	this.markers = {location: './modules/RealTimeInterface/images/', white: 'blue_circle.png', black: 'red_circle.png'};
	
	this.tiles_status = {on_tile: -1, selected_start: -1, selected_end: -1};
	this.tiles = [];
	this.pieces = [];
	this.Helper = new Helper();
	
	this.is_masked = false;
	
	// Callbacks
	this.moved_piece = undefined;
	this.promoted = undefined;
	
	this.LANG = {txt_promotion: 'Promotion'};
	
	// Sets up the chess board. Settings include:
	// The DOM element that the board will be put in.
	// pieces - array of pieces to place on board.
	// side - orientation of the board. If SIDE.white, rank 1 is at the bottom. If SIDE.black, rank 8 is at the bottom.
	// turn - the player whose turn it is.
	// width - the width of tiles. Default 50.
	// border -	the width of the border. Default 20.
	// colours - the colours to use for the board.
	// set - the location and file names for the chess pieces.
	this.setup = function(settings)
	{
		this.side = settings.side;
		this.tile_width = (settings.width !== undefined ? settings.width : 50);
		this.border_width = (settings.border_width !== undefined ? settings.border_width : 20);
		this.turn = settings.turn;
		
		if(settings.colours) this.colours = settings.colours;
		if(settings.set) this.set = settings.set;
		if(settings.LANG) this.LANG = settings.LANG;
		
		// Order that elements are created is important! What is created later will appear above earlier
		// elements.
		this.els.container = settings.container;
		this._create_border_elements();
		this._create_tiles();
		
		this.els.from_tile_marker = $('<div id="from_tile_marker"></div>');
		this.els.from_tile_marker.css({position: 'absolute', left: -9999, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.prv_move});
		this.els.container.append(this.els.from_tile_marker);
		
		this.els.to_tile_marker = $('<div id="to_tile_marker"></div>');
		this.els.to_tile_marker.css({position: 'absolute', left: -9999, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.prv_move});
		this.els.container.append(this.els.to_tile_marker);
		
		this._place_pieces(settings.pieces);
		
		this.els.highlight_tile = $('<div id="highlight_tile"></div>');
		this.els.highlight_tile.css({position: 'absolute', left: -9999, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.highlighted_tile, cursor: 'pointer'});
		this.els.container.append(this.els.highlight_tile);
		
		this.els.start_tile = $('<div id="start_tile"></div>');
		this.els.start_tile.css({position: 'absolute', left: 0, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.stile});
		this.els.container.append(this.els.start_tile);
		
		this._create_board_mask();
		this._create_board_mask2();
		this._create_promotion_dialog();
		this._add_event_handlers();
	}
	
	this._create_border_elements = function()
	{
		var top, left, bottom, right, main;
		this.els.border_top = top = $('<div id="_border_top"></div>');
		this.els.border_left = left = $('<div id="_border_left"></div>');
		this.els.border_right = right = $('<div id="_border_right"></div>');
		this.els.border_bottom = bottom = $('<div id="_border_bottom"></div>');
		this.els.main = main = $('<div id="_main"></div>');
		this.els.container.append(top, right, left, bottom, main);
		
		var total_width = this.tile_width * 8 + this.border_width * 2;
		
		var files = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
		var ranks = ['8', '7', '6', '5', '4', '3', '2', '1'];
		if(this.side == SIDE.black)
		{
			files = files.reverse();
			ranks = ranks.reverse();
		}
		
		// Generate 8 divs for the top border that will contain the files (A through H).
		top.css({position: 'absolute', left: '0px', top: '0px', width: total_width + 'px', height: this.border_width + 'px'}).addClass('board_border');
		var x = this.border_width;
		for(var i = 0; i < 8; i++)
		{
			top.append('<div class="board_border" style="position: absolute; top: 0px; left: ' + x + 'px; width: ' + this.tile_width + 'px; height: ' + this.border_width + 'px">' + files[i] + '</div>');
			x += this.tile_width;
		}
		
		// Generate 8 divs for the bottom border that will contain the files (A through H).
		bottom.css({position: 'absolute', left: '0px', top: total_width - this.border_width + 'px', width: total_width + 'px', height: this.border_width + 'px'}).addClass('board_border');
		x = this.border_width;
		for(var i = 0; i < 8; i++)
		{
			bottom.append('<div class="board_border" style="position: absolute; top: 0px; left: ' + x + 'px; width: ' + this.tile_width + 'px; height: ' + this.border_width + 'px">' + files[i] + '</div>');
			x += this.tile_width;
		}
		
		// Generate 8 divs for the left border that will contain the ranks (1 through 8).
		left.css({position: 'absolute', left: '0px', top: this.border_width + 'px', width: this.border_width + 'px', height: this.tile_width * 8 + 'px'}).addClass('board_border');
		var y = this.border_width / 1.33;	// It is hard to vertical align text. Therefore applied an offset instead of just 0.
		for(var i = 0; i < 8; i++)
		{
			left.append('<div class="board_border" style="position: absolute; top: ' + y + 'px; left: 0px; width: ' + this.border_width + 'px; height: ' + this.tile_width + 'px">' + ranks[i] + '</div>');
			y += this.tile_width;
		}
		
		// Generate 8 divs for the right border that will contain the ranks (1 through 8).
		right.css({position: 'absolute', left: total_width - this.border_width + 'px', top: this.border_width + 'px', width: this.border_width + 'px', height: this.tile_width * 8 + 'px'}).addClass('board_border');
		var y = this.border_width / 1.33;	// It is hard to vertical align text. Therefore applied an offset instead of just 0.
		for(var i = 0; i < 8; i++)
		{
			right.append('<div class="board_border" style="position: absolute; top: ' + y + 'px; left: 0px; width: ' + this.border_width + 'px; height: ' + this.tile_width + 'px">' + ranks[i] + '</div>');
			y += this.tile_width;
		}
		
		
	}

	this._create_tiles = function()
	{
		var side = SIDE.black; // (this.side == SIDE.white ? this.colours.wtile : this.colours.btile);
		var tile;
		// Tile 0,0 is A1 and tile 7,7 is H8
		for(var r = 0; r < 8; r++)
		{
			for(var f = 0; f < 8; f++)
			{
				var pos = this._index_to_pos(r * 8 + f);
				tile = $('<div id="tile_' + (r * 8 + f) + '"></div>');
				tile.css({
					position: 'absolute', 
					width: this.tile_width + 'px', 
					height: this.tile_width + 'px', 
					top: pos.y + 'px', 
					left: pos.x + 'px', 
					'background-color': (side == SIDE.white ? this.colours.wtile : this.colours.btile)
				});
				this.els.main.append(tile);	
				side = (side == SIDE.white ? SIDE.black : SIDE.white);
				this.tiles.push({highlighted: false, selected_start: false, selected_end: false});
			}
			side = (side == SIDE.white ? SIDE.black : SIDE.white);
		}
	}

	this._place_pieces = function(pieces)
	{
		for(var i = 0; i < 64; i++)
			this.pieces.push(undefined);
		for(var i = 0; i < pieces.length; i++)
		{
			var piece = pieces[i];
			var el = $('<img class="piece" src=""/>');
			var pos = this._index_to_pos(piece.y * 8 + piece.x);
			var url = this.set.location + (piece.type <= PIECE_TYPE.wPawn ? this.set.white_files[piece.type - 1] : this.set.black_files[piece.type - 7]);
			el.css({position: 'absolute', left: pos.x + 'px', top: pos.y + 'px', width: this.tile_width, height: this.tile_width});
			el.attr('src', url);
			this.els.container.append(el);
			this.pieces[piece.y * 8 + piece.x] = {type: piece.type, el: el};
		}
		//console.log(this.pieces);
	}
	
	this._create_board_mask = function()
	{
		var el = $('<div id="board_mask"></div>');
		var board_size = self.tile_width * 8 + self.border_width * 2;
		el.css({position: 'absolute', width: board_size, height: board_size, display: 'none'});
		self.els.container.append(el);
		self.els.board_mask = el;
	}
	
	this._create_board_mask2 = function()
	{
		var el = $('<div id="board_mask2"></div>');
		var board_size = self.tile_width * 8 + self.border_width * 2;
		el.css({position: 'absolute', width: board_size, height: board_size, display: 'none'});
		self.els.container.append(el);
		self.els.board_mask2 = el;
	}
	
	this._create_promotion_dialog = function()
	{
		var width = self.tile_width;
		var files = (self.side == SIDE.white ? self.set.white_files : self.set.black_files);
		var el = $('<div id="promotion_dialog">' + this.LANG.txt_promotion + '</div>');
		el.css({position: 'absolute', width: self.tile_width * 5 - 10, height: self.tile_width * 3 - 10, left: self.border_width + self.tile_width * 1.5, top: self.border_width + self.tile_width * 2.5, 'background-color': 'white', border: '1px solid gray', 'text-align': 'center', padding: 5, opacity: 0.9, filter: 'alpha(opacity=90)', display: 'none'});
		self.els.container.append(el);
		self.els.promotion_dialog = el;
		var left = 0.2;
		var ids = ['queen', 'bishop', 'knight', 'rook'];
		for(var i = 0; i < 4; i++)
		{
			var el2 = $('<div class="promotion_item" id="promote_to_' + ids[i] + '" piece="' + (i + 2) + '"></div>');
			el2.css({position: 'absolute', width: width - 2, height: width - 2, top: width * 1.5, left: width * left, cursor: 'pointer', border: '1px solid gray', 'background-image': 'url("' + self.set.location + files[i + 1] + '")'});
			el.append(el2);
			left += 1.2;
		}
	}
	
	this._add_event_handlers = function()
	{
		var is_mobile = "ontouchend" in document;
		if(!is_mobile)
		{
			$(this.els.container).mousemove(function(e){self._moved_mouse(self, e)});
			$(this.els.container).click(function(e){self._clicked_mouse(self, e)});
			$('.promotion_item').click(function(e){
				self.moved_piece(self.tiles_status.selected_start, self.tiles_status.selected_end, parseInt($(this).attr('piece'), 10));
				e.stopPropagation();		// Don't want the click to be handled by the container element.
			});
		}
		else
		{
			$(this.els.container).click(function(e){self._clicked_mouse(self, e)});
			$('.promotion_item').click(function(e){
				self.moved_piece(self.tiles_status.selected_start, self.tiles_status.selected_end, parseInt($(this).attr('piece'), 10));
				e.stopPropagation();		// Don't want the click to be handled by the container element.
			});
		}
	}
	
	// Converts a tile index to a x,y position on the board.
	// index: the tile index (0 to 63)
	// location: can define to what point the tile should resolve to, since the tile occupies an area.
	//           Can choose from 'top-left' or 'center'. Default is 'top-left'.
	this._index_to_pos = function(index, location)
	{
		var rank, file, x, y;
		location = (location !== undefined ? 'center' : location);
		
		rank = Math.floor(index / 8);
		file = index % 8;
		if(this.side == SIDE.white)
			rank = 7 - rank;
		else
			file = 7 - file;
		
		x = this.border_width + this.tile_width * file;
		y = this.border_width + this.tile_width * rank;
		if(location == 'center')
		{
			x += this.tile_width / 2;
			y += this.tile_width / 2;
		}
		//var  i = this._pos_to_index(x - 50, y - 0);
		//var co = this._index_to_coord(i)
		//console.log(co, i, index, this._coord_to_index(co));
		
		return {x: x, y: y};
	}
	
	// Converts a x,y position to a tile index, or the closest tile if the x and/or y are out of bounds.
	// The tile index 0 == a1 and tile index 63 == h8.
	this._pos_to_index = function(x, y)
	{
		var rank, file;
		var top = left = this.border_width;
		var bottom = right = top + this.tile_width * 8;
		
		if(x <= left)
			file = 0;
		else if(x >= right)
			file = 7;
		else
			file = Math.floor((x - left ) / this.tile_width);
		if(y <= top)
			rank = 0;
		else if(y >= bottom)
			rank = 7;
		else
			rank = Math.floor((y - top) / this.tile_width);
		
		if(this.side == SIDE.white)
			rank = 7 - rank;
		else
			file = 7 - file;
		
		return rank * 8 + file;
	}
	
	// Converts a tile index to a tile coord string (0 = 'a1', 63 = 'h8').
	this._index_to_coord = function(index)
	{
		var files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
		return files[index % 8] + (Math.floor(index / 8) + 1);
	}
	
	// Converts a tile coord string to a tile index ('a1' = 0, 'h8' = 63).
	this._coord_to_index = function(index)
	{
		var files = {a: 0, b: 1, c: 2, d: 3, e: 4, f: 5, g: 6, h: 7};
		return (index[1] - 1) * 8 + files[index[0]];
	}

	this._get_tile_clicked = function(board, x, y)
	{
		var pos = board.els.container.offset();
		x -= pos.left;
		y -= pos.top;
		return board._pos_to_index(x, y);
	}
	
	this._moved_mouse = function(board, e)
	{
		if(self.is_masked) return;
		var pos = board.els.container.offset();
		var x = e.pageX - pos.left;
		var y = e.pageY - pos.top;
		var i = board._pos_to_index(x, y);
		if(board.tiles_status.on_tile != i)
			board.changed_mouse_over_tile(i, board.tiles_status.on_tile);
	}
	
	this._clicked_mouse = function(board, e)
	{	//$('#debug').append('<div>CLicked: ' + board.tiles_status.selected_start  + '</div>');
		if(self.is_masked) return;
		
		// var cur = board.tiles_status.on_tile
		var cur = self._get_tile_clicked(board, e.pageX, e.pageY);
		//console.log('Clicked on tile ' + board._index_to_coord());
		// If no piece is selected, select it if it belongs to the player. When a piece is selected, then
		// check if the destination piece is empty or contains an enemy piece. In that case fire the move event.
		if(board.tiles_status.selected_start == -1)
		{
			if(board.pieces[cur] != undefined && board.Helper.get_side_from_piece(board.pieces[cur].type) == board.turn)
			{	// board._position_starttile(board.tiles_status.on_tile);
				board._position_starttile(cur);
				//$('#debug').append('<div>Selected start tile</div>');
			}
		}
		else
		{
			if(cur == board.tiles_status.selected_start) // Deselect piece
			{
				//$('#debug').append("<div>Deselected start tile</div>");
				board._position_starttile(-1);
				board._position_highlight(-1);
				return;
			}
			if(board.pieces[cur] == undefined || board.Helper.get_side_from_piece(board.pieces[cur].type) != board.turn)
			{
				if(board.moved_piece !== undefined)
				{
					//board.tiles_status.selected_end = cur;
					var ret = board.moved_piece(board.tiles_status.selected_start, cur);
					// console.log('ret = ' + ret);
					if(ret == "promote")
					{
						self.tiles_status.selected_end = cur;
						self.show_mask();
						self.show_promotion_dialog();
						return;
					}
				}
				board._position_starttile(-1);
				board._position_highlight(-1);
			}
		}
	}
	
	this.changed_mouse_over_tile = function(cur, old)
	{
	//console.log(cur, old);
		this.tiles_status.on_tile = cur;
		// If the user has not selected a tile then highlight tile over if it has one of the current player's pieces.
		// Otherwise only highlight empty tiles or tiles with pieces of the other player.
		if(this.tiles_status.selected_start == -1)
		{
			if(this.pieces[cur] == undefined || this.Helper.get_side_from_piece(this.pieces[cur].type) != this.turn)
				this._position_highlight(-1);
			else
				this._position_highlight(cur);
		}
		else
		{
			if(this.pieces[cur] == undefined || this.Helper.get_side_from_piece(this.pieces[cur].type) != this.turn)
				this._position_highlight(cur);
			else
				this._position_highlight(-1);
		}
	}
	
	this._position_highlight = function(index)
	{
		if(index == -1)
			this.els.highlight_tile.css({display: 'none'});
		else
		{
			var pos = this._index_to_pos(index);
			this.els.highlight_tile.css({left: pos.x, top: pos.y, display: 'block'});
		}
	}
	
	this._position_starttile = function(index)
	{
		if(index == -1)
			this.els.start_tile.css({display: 'none'});
		else
		{
			var pos = this._index_to_pos(index);
			this.els.start_tile.css({left: pos.x, top: pos.y, display: 'block'});
		}
		this.tiles_status.selected_start = index;
	}
	
	this._position_from_tile_marker = function(index)
	{
		var el = self.els.from_tile_marker;
		var file = (self.turn == SIDE.white ? self.markers.white : self.markers.black);
		var pos = self._index_to_pos(index);
		el.show();
		el.css({left: pos.x, top: pos.y});
	}
	
	this._position_to_tile_marker = function(index)
	{
		var el = self.els.to_tile_marker;
		var file = (self.turn == SIDE.white ? self.markers.white : self.markers.black);
		var pos = self._index_to_pos(index);
		el.show();
		el.css({left: pos.x, top: pos.y});
	}
	
	this.position_lastmove_markers = function(from, to)
	{
		self._position_from_tile_marker(from);
		self._position_to_tile_marker(to);
	}
	
	this.move_piece = function(from, to)
	{
		var piece = self.pieces[to];
		if(piece !== undefined)
			piece.el.css({display: 'none'});
		var piece = self.pieces[from];
		self.pieces[from] = undefined;
		self.pieces[to] = piece;
		var pos = self._index_to_pos(to);
		piece.el.css({left: pos.x, top: pos.y});
		self._position_starttile(-1);
		self._position_highlight(-1);
		self.position_lastmove_markers(from, to);
	}
	
	
	this.remove_piece = function(index)
	{
		var piece = self.pieces[index];
		if(piece !== undefined)
			piece.el.css({display: 'none'});
		self.pieces[index] = undefined;
	}
	
	this.change_piece_type = function(index, type)
	{
		// console.log('changing to type: ', type);
		self.pieces[index].type = type;
		type--;	// Adjust for use in image arrays.
		//var url = self.set.location + (self.side == SIDE.white ? self.set.white_files[type] : self.set.black_files[type - 6]);
		var url = self.set.location + (type < 6 ? self.set.white_files[type] : self.set.black_files[type - 6]);
		self.pieces[index].el.attr({'src': url});
	}
	
	this.get_pieces = function()
	{
		return this.pieces;
	}
	
	this.show_promotion_dialog = function()
	{
		self.els.promotion_dialog.show();
	}
	this.hide_promotion_dialog = function()
	{
		self.els.promotion_dialog.hide();
	}
	
	this.show_mask = function()
	{
		self.els.board_mask.show();
		self.is_masked = true;
	}
	this.hide_mask = function()
	{
		self.els.board_mask.hide();
		self.is_masked = false;
	}
	this.show_game_over_mask = function()
	{
		self.els.board_mask2.show();
		self.is_masked = true;
	}
}


function ChessGame()
{
	var self = this;
	this.side;				// Side being played. Determines orientation of board.
	this.turn;				// White or Black's turn.
	this.session = '';		// Player's session string needed for sending requests to the server.
	this.gameid = '';		// The ID of the game.
	this.Helper = new Helper();	// Helper functions.
	this.Board;
	this.pending_move = {from: undefined, to: undefined, promotion: undefined, from2: undefined, to2: undefined, enpassant: undefined};
	this.request_timings;	// Holds the time intervals between different data requests.
	this.timeout_ids = {chat: undefined, move: undefined};		// Holds the IDs used with the request timeouts.
	this.game_state = CHESS_BOARD_STATUS.NORMAL;
	this.game_result = CHESS_GAME_RESULT.UNKNOWN;
	this.game_draw_status = CHESS_GAME_DRAW_STATUS.NONE;
	this.Chat = {input_ctrl: undefined, send_ctrl: undefined, output_ctrl: undefined};
	
	this.get_new_move = true;
	this.get_game_over = true;
	this.game_update_interval = undefined;
	
	//this.timeleft = {started: 0, duration: 0};
	this.Clock;
	
	// Variables for err handling
	this.xhr_move_error_count = 0;	// Keep track of the number of communication errors when sending moves.
	this.xhr_state_error_count = 0;	// Keep track of the number of communication errors when getting the game state.
	
	
	this.init = function(settings)
	{
		//console.log(settings);
		this.session = settings.sessionid;
		this.gameid = settings.gameid;
		this.turn = settings.turn;
		// console.log("Turn is ", this.turn);
		this.side = settings.side;
		this.request_timings = settings.request_timings;
		var pieces = this.Helper.get_pieces_from_FEN(settings.fen);
		settings['pieces'] = pieces;
		settings['LANG'] = LANG;
		this.Board = new ChessBoard();
		this.Board.setup(settings);
		this.Board.moved_piece = this.make_move;
		//this.Chat = settings.chat;
		
		this.setup_visual_elements();
		this.attach_event_handlers();
		
		
		
		//try 
		//{
			this.init_time_remaining($.parseXML(settings.initial_game_state));
			this.processPGN($.parseXML(settings.initial_game_state));
			this.process_game_state(this.turn == this.side);
		//}
		//catch(ex)
		//{
		//	alert('Error parsing intial game state.');
		//}
		
		if(this.turn != this.side && this.game_result == CHESS_GAME_RESULT.UNKNOWN)
		{
			this.Board.show_mask();
			this.game_update_interval = this.request_timings.move;
		}
		else
		{
			this.get_new_move = false;
			this.game_update_interval = this.request_timings.game_over;
		}
		if(this.game_result == CHESS_GAME_RESULT.UNKNOWN)
			this.timeout_ids.move = setTimeout(this.check_for_game_state_change, this.game_update_interval);
		this.init_chat(settings.chat);
		//console.log(settings.initial_game_state)
		
		if(settings.last_move !== false)
		{
			var tiles = settings.last_move.split(' ');
			this.Board.position_lastmove_markers(this.Helper.coord_to_index(tiles[0]),this.Helper.coord_to_index(tiles[1]));
		}
	}
	
	this.make_move = function(from, to, promotion)
	{
		//console.log('Make move: ', self.Helper.index_to_coord(from), self.Helper.index_to_coord(to), promotion);
		
		var url='mobile.php?action=move&sid=' + self.session + '&gameid=' + self.gameid + '&from=';
		url += self.Helper.index_to_coord(from) + '&to=' + self.Helper.index_to_coord(to);
		//console.log('promotion', promotion);
		if(promotion !== undefined)
		{
			var chars = ['Q', 'B', 'N', 'R'];
			url += chars[promotion - 2];
		}
		
		//console.log(url);
		// If the move resulted in a pawn reaching the far end and no promotion pieces was selected, 
		// need to ask the board to show the promotion dialog.
		if(self._is_promotion_required(from, to) && promotion == undefined)
		{
			return "promote";
		}
		self.Board.hide_promotion_dialog();
		// Store move made to be applied when server OKs the move.
		self.pending_move = {from: undefined, to: undefined, promotion: undefined, from2: undefined, to2: undefined, enpassant: undefined};
		self.pending_move.from = from;
		self.pending_move.to = to;
		if(promotion !== undefined)
			self.pending_move.promotion = (self.turn == SIDE.white ? promotion : promotion + 6);
		//console.log(self.pending_move);
		// Send move request to server.
		$.get(url, self.make_move_response).error(self.server_error_move); //error(function(){alert(LANG.txt_unable_to_send_move)});
		self.Board.show_mask();
		//self.make_move_response('');
	}
	
	this.make_move_response = function(data)
	{
		self.xhr_move_error_count = 0;
		//if(data.getElementsByTagName('MOVE').item(0).firstChild.data=='false')
		if($(data).find('MOVE').text() == 'false')
		{
			self.Board.hide_mask();
			//console.log('invalid move');
			return;
		}
		// Move is valid. Commit the move on the board (normal move, castling, promotion and en
		// passant checks are required).
		
		if(self.pending_move.promotion !== undefined)
		{
			//console.log('is promotion move', self.pending_move);
			self.Board.move_piece(self.pending_move.from, self.pending_move.to);
			self.Board.change_piece_type(self.pending_move.to, self.pending_move.promotion);
		}
		else
		{
			var san = $(data).find('MOVE_SAN').text();
			var to = $(data).find('MOVE_TO').text();		// as coord
			var from = $(data).find('MOVE_FROM').text();	// as coord	
		// console.log('lastmove san: ' , san, from, to);
			if(self._is_move_castling(san))
			{
				// console.log('is castling: ', self.pending_move);
				self.Board.move_piece(self.pending_move.from2, self.pending_move.to2);
				self.Board.move_piece(self.pending_move.from, self.pending_move.to);
				//self.Board.position_lastmove_markers(self.pending_move.from, self.pending_move.to);
			}
			else if(self._is_move_promotion(san))
			{
				self.Board.move_piece(self.pending_move.from, self.pending_move.to);
			}
			else if(self._is_move_en_passant(from, to))
			{
				self.Board.move_piece(self.pending_move.from, self.pending_move.to);
				self.Board.remove_piece(self.pending_move.enpassant);
			}
			else
			{
				self.Board.move_piece(self.pending_move.from, self.pending_move.to);
			}
		}
		
		// Inform the clock of a turn change and provide correct times used by the players.
		var times = {
			correct_time: {
				white: parseInt($(data).find('TIME_W_LEFT').text()),
				black: parseInt($(data).find('TIME_B_LEFT').text())
			}
		};
		self.Clock.changed_turn(times);
		
		// Update the PGN and taken pieces list.
		self.processPGN(data);
		
		// Check the gamestate for check or if the game is over.
		self.process_game_state(false);
		
		self.turn = (self.turn == SIDE.white ? SIDE.black : SIDE.white);
		self.Board.turn = self.turn;
		//self.check_for_game_state_change();
		
		// Change to fetch new move and game over updates
		self.get_new_move = true;
		self.game_update_interval = self.request_timings.move;
	}
	
	this.server_error_move = function()
	{
		self.xhr_move_error_count++;
		if(self.xhr_move_error_count < 4)
			self.make_move(self.pending_move.from, self.pending_move.to, self.pending_move.promotion);
		else
			alert(LANG.txt_unable_to_send_move);
	}
	
	// Sends a request for a move and game over update
	this.check_for_game_state_change = function()
	{
		self.pending_move = {from: undefined, to: undefined, promotion: undefined, from2: undefined, to2: undefined, enpassant: undefined};
		//console.log(self);
		var url = 'mobile.php?action=get_game_update_on_state_change&sid=' + self.session + '&gameid=' + self.gameid + '&side_to_move=' + (self.turn == SIDE.white ? 'w' : 'b') + '&get_game_over=' + (self.get_game_over ? '1' : '0') + '&get_new_move=' + (self.get_new_move ? '1' : '0') + '&with_full_update=true';
		$.get(url, self.game_state_change_response).error(self.server_error_state);//(function(){alert(LANG.txt_unable_to_query_game_state)});
	}
	
	this.game_state_change_response = function(data)
	{
	
		/* If new move and game over are both true, apply the move, process the PGN and game state,
		   and stop fetching updates.
		   If new move, but the game is not over, apply the move, process the PGN and game state.
		   If game over, but no new move, process the PGN and game state, and stop fetching updates.
		*/
	
		var new_move = false;
		var game_over = false;
		var update_again = true;
		var draw = false;
	
		//console.log($(data).find('GAME_OVER'), $(data).find('NEW_MOVE'));
		try
		{
			if($(data).find('GAME_OVER').length > 0 && $(data).find('GAME_OVER').text() == 'true')
				game_over = true;
			if($(data).find('NEW_MOVE').length > 0 && $(data).find('NEW_MOVE').text() == 'true')
				new_move = true;
				
			if(new_move)
			{
				// Got a new move.
				self.Board.hide_mask();
				var from = $(data).find('MOVE_FROM').text();
				var to = $(data).find('MOVE_TO').text();
				var san = $(data).find('MOVE_SAN').text();
				self.pending_move.from = self.Helper.coord_to_index(from);
				self.pending_move.to = self.Helper.coord_to_index(to);
				//console.log('got move:', from, to, san);
				if(self._is_move_castling(san))
				{
				//console.log('castling move');
					self.Board.move_piece(self.pending_move.from2, self.pending_move.to2);
					self.Board.move_piece(self.pending_move.from, self.pending_move.to);
				}
				else if(self._is_move_promotion(san))
				{
				//console.log('promotion move');
					self.Board.move_piece(self.pending_move.from, self.pending_move.to);
					self.Board.change_piece_type(self.pending_move.to, self.pending_move.promotion);
				}
				else if(self._is_move_en_passant(from, to))
				{
				//console.log('en passant move');
					self.Board.move_piece(self.pending_move.from, self.pending_move.to);
					self.Board.remove_piece(self.pending_move.enpassant);
				}
				else	// normal move
				{
				//console.log('normal move');
					self.Board.move_piece(self.pending_move.from, self.pending_move.to);
				}
				
				self.turn = (self.turn == SIDE.white ? SIDE.black : SIDE.white);
				self.Board.turn = self.turn;
				self.Board.hide_mask();
				
				// Inform the clock of a turn change and provide correct times used by the players.
				var times = {
					correct_time: {
						white: parseInt($(data).find('TIME_W_LEFT').text()),
						black: parseInt($(data).find('TIME_B_LEFT').text())
					}
				};
				self.Clock.changed_turn(times);
			}
			
			if(game_over)
			{
				update_again = false;
			}
			
			if(game_over || new_move)
			{
				self.processPGN(data);
			}
			else	// Check if draw request change has occured.
			{
				var draw = $(data).find('DRAWCODE').text();
				if(draw == 'IDS_USER_REQUESTED_DRAW')
					self.game_draw_status = CHESS_GAME_DRAW_STATUS.SELF;
				else if(draw == 'IDS_DRAW_REQUESTED')
					self.game_draw_status = CHESS_GAME_DRAW_STATUS.OPPONENT;
				else if(draw == 'IDS_DRAW')
					self.game_draw_status = CHESS_GAME_DRAW_STATUS.DRAW;
				else
					self.game_draw_status = CHESS_GAME_DRAW_STATUS.NONE;
			}
			self.process_game_state(false);
		}
		catch(exception)
		{
			window.location.reload();
		}
		
		if(update_again)
		{
			if(new_move)
			{
				// Change to only fetching game over updates
				self.get_new_move = false;
				self.game_update_interval = self.request_timings.game_over;
			}
			self.timeout_ids.move = setTimeout(self.check_for_game_state_change, self.game_update_interval);
		}
		
	}
	
	// If an game state update failed, then try again. After 3 failures show an error and stop.
	this.server_error_state = function()
	{
		self.xhr_state_error_count++;
		if(self.xhr_state_error_count < 4)
			self.timeout_ids.move = setTimeout(self.check_for_game_state_change, self.game_update_interval);
		else
			alert(LANG.txt_unable_to_query_game_state);
	}
	
	this.init_chat = function(settings)
	{
		this.Chat.input_ctrl = $('#' + settings.input_ctrl);
		this.Chat.send_ctrl = $('#' + settings.send_ctrl);
		this.Chat.output_ctrl = $('#' + settings.output_ctrl);
		// Pressing enter in the chat textbox will send the message.
		this.Chat.input_ctrl.keypress(function(event) {
			if ( event.which == 13 ) self.send_chat_message();
		});
		// Or can press the send button.
		this.Chat.send_ctrl.click(function(){self.send_chat_message()});
		//this.timeout_ids.chat = setTimeout(this.check_for_chat_message, this.request_timings.chat);
		// Get the chat messages.
		self.check_for_chat_message();
	}
	
	this.check_for_chat_message = function()
	{
		var url='mobile.php?action=getgamechat&sid=' + self.session + '&gameid=' + self.gameid;
		$.get(url, self.chat_message_update).error(function(event, jqxhr, settings, exception){
			//console.log(event, jqxhr, settings, exception);
			self.timeout_ids.chat = setTimeout(self.check_for_chat_message, self.request_timings.chat);
		});
	}
	
	this.chat_message_update = function(data)
	{
		var i, cb, msglist, msg='';
		// cb = $('#chatbox');
		cb = self.Chat.output_ctrl;
		if(data.getElementsByTagName('MSG').item(0).firstChild != null)
		{
			msglist = data.getElementsByTagName('MSG').item(0).firstChild.data.split('\n');
			for(i = 0; i < msglist.length; i++)
			{
				if(msglist[i] != '')
				{
					msglist[i] = msglist[i].replace(/<(\w+)>/, "<strong>&lt;$1&gt;</strong>");
					msg += msglist[i] + '<br>';
				}
			}
			cb.html(msg);
			//cb.scrollTop = cb.scrollHeight;
		}
		self.timeout_ids.chat = setTimeout(self.check_for_chat_message, self.request_timings.chat);
	}
	
	this.send_chat_message = function()
	{
		var ctrl = self.Chat.input_ctrl;
		var url = 'mobile.php?action=sendgamechat&sid=' + self.session + '&gameid=' + self.gameid + '&msg=' + escape(ctrl.val());
		$.get(url).error();
		ctrl.val("");
		self.check_for_chat_message();
	}
	
	
	this._is_promotion_required = function(from, to)
	{
		var pieces = self.Board.get_pieces();
		var rank = Math.floor(to / 8);
		var type = self.Helper.get_general_type_of_piece(pieces[from].type);
		if(type == GENERAL_PIECE_TYPE.pawn)
		{
			if(self.side == SIDE.white && rank == 7 || self.side == SIDE.black && rank == 0)
				return true;
		}
		return false;
	}
	
	this._is_move_promotion = function(move)
	{
		var pos = move.indexOf('=');
		if(pos == -1) return false;
		var type = move[pos + 1];
		//console.log('is promotion with type: ', type);
		var chars = {Q: 2, B: 3, N: 4, R: 5};
		type = chars[type];
		if(this.turn == SIDE.black) type += 6;
		//console.log('tpye is now: ', type);
		this.pending_move.promotion = type;
		return true;
	}
	
	this._is_move_en_passant = function(from, to)
	{
		var f1 = from[0];
		var f2 = to[0];
		var from = this.Helper.coord_to_index(from);
		var to = this.Helper.coord_to_index(to);
		var piece = this.Board.pieces[from];
		//console.log(piece, from, to);
		var type = this.Helper.get_general_type_of_piece(piece.type);
		//console.log('type: ', type, 'piece: ', piece, 'dest: ', this.Board.pieces[to]);
		if(!(type == GENERAL_PIECE_TYPE.pawn && this.Board.pieces[to] == undefined)) return false;
		//console.log('f1:', f1, 'f2: ', f2);
		if(f1 == f2) return false;
		var captured = to;
		//console.log(from, to, 'side:', this.turn);
		if(this.turn == SIDE.white)
			captured -= 8;
		else
			captured += 8;
		//console.log('captured: ', captured, this.Helper.index_to_coord(captured));
		this.pending_move.from = from;
		this.pending_move.to = to;
		this.pending_move.enpassant = captured;
		return true;
	}
	
	this._is_move_castling = function(move)
	{
		var castling = false;
		if(move == 'O-O')	// Kingside
		{
			if(self.turn == SIDE.white)
			{
				self.pending_move.from2 = self.Helper.coord_to_index('h1');
				self.pending_move.to2 = self.Helper.coord_to_index('f1');
			}
			else
			{
				self.pending_move.from2 = self.Helper.coord_to_index('h8');
				self.pending_move.to2 = self.Helper.coord_to_index('f8');
			}
			castling = true;
		}
		else if(move == 'O-O-O')	// Queenside
		{
			if(self.turn == SIDE.white)
			{
				self.pending_move.from2 = self.Helper.coord_to_index('a1');
				self.pending_move.to2 = self.Helper.coord_to_index('d1');
			}
			else
			{
				self.pending_move.from2 = self.Helper.coord_to_index('a8');
				self.pending_move.to2 = self.Helper.coord_to_index('d8');
			}
			castling = true;
		}
		return castling;
	}

	// Process the current PGN for the game (it also contains non PGN fields which are used 
	// to convey the state of the game).
	this.processPGN = function(data)
	{
		// Extract just the moves from the PGN.
		var pgn = data.getElementsByTagName('PGN').item(0).firstChild.data;
		var P = new PGNParser();
		var parts = P.parse(pgn);

		pgn=pgn.replace(/\[\w*\W*\w*\W*\w*\W*\]/g, "");
		if(pgn == '') return;
		// var moves = pgn.split(/\s+[0-9]+\./);
		var moves = parts.movetext.split(/\s+[0-9]+\./);
		var move_tbl = '<table>';
		var move_cnt = 1;
		var start = false;
		for(var i = 0; i < moves.length; i++)
		{
			if(moves[i] != '')
			{
				var parts = moves[i].split(' ');
				move_tbl += '<tr><td>' + move_cnt + '.</td><td>' + parts[1] + '</td>';
				if(parts.length > 2)
				{
					start = true;
					move_tbl += '<td>' + parts[2] + '</td></tr>';
				}
				move_cnt++;
			}
		}
		if(start)	// start clock only after the first half move is made
			self.Clock.start_clock();
		move_tbl += '</table>';
		pgn=pgn.replace(/(\w*\.[0-9]*)/g, "\n<br>$1");
		//console.log(move_tbl);
		$('#pgntext').html(move_tbl);
		
		// Update the captured pieces lists.
		var bpiece_map = {P: 'wpw.gif', R: 'wrw.gif', N: 'wnw.gif', B: 'wbw.gif', Q: 'wqw.gif', K: 'wkw.gif'};
		var wpiece_map = {P: 'bpw.gif', R: 'brw.gif', N: 'bnw.gif', B: 'bbw.gif', Q: 'bqw.gif', K: 'bkw.gif'};

		var html = '';
		var taken = $(data).find('CAPTURED_BY_WHITE').text();
		if(taken)
		{
			var pieces = taken.split(', ');
			for(var i = 0; i < pieces.length; i++)
			{
				html += '<img src="modules/RealTimeInterface/img_chess/' + wpiece_map[pieces[i]] + '" width="30px" />';
			}
			$('#taken_pieces_white').html(html);
		}
		taken = $(data).find('CAPTURED_BY_BLACK').text();
		if(taken)
		{
			html = '';
			var pieces = taken.split(', ');
			for(var i = 0; i < pieces.length; i++)
			{
				html += '<img src="modules/RealTimeInterface/img_chess/' + bpiece_map[pieces[i]] + '" width="30px" />';
			}
			$('#taken_pieces_black').html(html);
		}
		
		this.game_state = $(data).find('GAME_STATE').text();
		this.game_result = $(data).find('GAME_RESULT').text();

		// Draw requested
		var draw = $(data).find('DRAWCODE').text();
		if(draw == 'IDS_USER_REQUESTED_DRAW')
			this.game_draw_status = CHESS_GAME_DRAW_STATUS.SELF;
		else if(draw == 'IDS_DRAW_REQUESTED')
			this.game_draw_status = CHESS_GAME_DRAW_STATUS.OPPONENT;
		else if(draw == 'IDS_DRAW')
			this.game_draw_status = CHESS_GAME_DRAW_STATUS.DRAW;
		else
			this.game_draw_status = CHESS_GAME_DRAW_STATUS.NONE;
	}
	
	this.process_game_state = function(made_move)
	{
		// Draw requested?
		//console.log(this.game_draw_status);
		if(this.game_draw_status == CHESS_GAME_DRAW_STATUS.SELF)
		{
			this.show_draw_request_self();
		}
		else if(this.game_draw_status == CHESS_GAME_DRAW_STATUS.OPPONENT)
		{
			this.show_draw_request_opponent();
		}
		else if(this.game_draw_status == CHESS_GAME_DRAW_STATUS.NONE)
		{
			$('#game_draw_requests').empty();
			$('#draw input').show(500);
		}
		
		// Check the game state. If check, display check message. If draw, display draw message. If 
		// win or loss display message.
		if(this.game_state == CHESS_BOARD_STATUS.NORMAL)
		{
			$('#game_status_message').html('').hide();
		}
		if(this.game_state == CHESS_BOARD_STATUS.CHECK)
		{
			if(made_move)
				$('#game_status_message').html(LANG.txt_check).show();
		}
		else if(this.game_state == CHESS_BOARD_STATUS.MATE)
		{
			if(made_move)
				$('#game_status_message').html(LANG.txt_mate).show();
			if(this.Clock) this.Clock.stop_clock();
		}
		
		if(this.game_result == CHESS_GAME_RESULT.DRAW)
		{
			this.show_draw();
			window.clearTimeout(this.timeout_ids.move);
			if(this.Clock) this.Clock.stop_clock();
			return;
		}
		
		if(this.game_result != CHESS_GAME_RESULT.UNKNOWN)
		{
			// Halt requesting move updates when the game is over. Make sure the board is masked 
			// and that the draw and resign buttons are not visible.
			window.clearTimeout(this.timeout_ids.move);
			this.Board.show_game_over_mask();
			$('#controls').hide();
			if(this.game_result == CHESS_GAME_RESULT.WHITEWIN)
			{
				if(this.side == SIDE.white)
					this.show_win();
				else
					this.show_loss();
			}
			else if(this.game_result == CHESS_GAME_RESULT.BLACKWIN)
			{
				if(this.side == SIDE.white)
					this.show_loss();
				else
					this.show_win();
			}
			
		}
		
	}
	
	
	this.setup_visual_elements = function()
	{	
		// Remove the rating points
		$('#bottomdiv div.points').remove();
		$('#topdiv div.points').remove();
		if(this.side == SIDE.white)
		{
			// Place the player clocks
			$('#topdiv div.userid').after($('#clock_b'));
			$('#bottomdiv div.userid').after($('#clock_w'));
		}
		else
		{
			// Place the player clocks
			$('#topdiv div.userid').after($('#clock_w'));
			$('#bottomdiv div.userid').after($('#clock_b'));
		}
	}
	
	this.attach_event_handlers = function()
	{
		$('#draw input').click(function(){self.request_draw()});
		$('#resign input').click(function(){self.request_resign()});
		// $('#d').click(function(){self.show_draw()});
		// $('#w').click(function(){self.show_win()});
		// $('#l').click(function(){self.show_loss()});
	}
	
	this.request_draw = function()
	{
		if(confirm(LANG.txt_request_draw))
		{
			var url = 'mobile.php?action=drawgame&sid=' + this.session + '&gameid=' + this.gameid;
			$.get(url, function(){
				self.show_draw_request_self();
			}).error();
		}
	}
	
	this.show_draw_request_self = function()
	{
		$('#game_draw_requests').html(LANG.txt_self_draw + '&nbsp;<button id="revoke_draw" style="width: auto" class="mainoption">' + LANG.txt_draw_revoke + '</button>').show(500);
		$('#draw input').hide(500);
		$('#revoke_draw').click(function(){self.revoke_draw()});
	}
	
	this.show_draw_request_opponent = function()
	{
		$('#game_draw_requests').html(LANG.txt_draw_request.replace(/%name%/, 'Your opponent') + '&nbsp;<button id="accept_draw" style="width: auto" class="mainoption">' + LANG.txt_draw_accept + '</button>').show(500);
		$('#draw input').hide(500);
		$('#accept_draw').click(function(){self.accept_draw()});
	}
	
	this.accept_draw = function()
	{
		var url = 'mobile.php?action=acceptdrawgame&sid=' + this.session + '&gameid=' + this.gameid;
		$.get(url, function(data){
			// Check if the game is now a draw. If not it means the opponent has removed the draw request.
			var draw = $(data).find('DRAWCODE').text();
			if(draw == 'IDS_DRAW')
				self.show_draw();
			else
			{
				alert('Opponent withdrew draw request before you accepted');
				self.update_revoke_draw();
			}
		}).error();
	}
	
	this.revoke_draw = function()
	{
		//console.log('revoke');
		var url = 'mobile.php?action=revokedrawgame&sid=' + this.session + '&gameid=' + this.gameid;
		$.get(url, function(data){
			var draw = $(data).find('DRAWCODE').text();
			if(draw == 'IDS_DRAW')
				self.show_draw();
			else
				self.update_revoke_draw();
		}).error();
	}
	
	this.update_revoke_draw = function()
	{
		$('#game_draw_requests').empty();
		$('#draw input').show(500);
	}
	
	this.request_resign = function()
	{
		if(confirm(LANG.txt_self_resign))
		{
			var url = 'mobile.php?action=resigngame&sid=' + this.session + '&gameid=' + this.gameid;
			$.get(url, function(){
				self.show_loss();
			}).error();
		}
	}
	
	this.show_win = function()
	{
		$('#game_draw_requests').empty();
		$('#game_status_message').html(LANG.txt_won).show();
		$('#controls').hide();
		this.show_game_finished_options();
		
		this.show_anim_text(LANG.txt_won);
		
	}
	
	this.show_loss = function()
	{
		$('#game_draw_requests').empty();
		$('#game_status_message').html(LANG.txt_lost).show();
		$('#controls').hide();
		this.show_game_finished_options();
		
		this.show_anim_text(LANG.txt_lost);
	}
	
	this.show_draw = function(is_active)
	{
		$('#game_draw_requests').empty();
		$('#game_status_message').html(LANG.txt_draw).show();
		$('#resign input').hide();
		this.Board.hide_mask();
		this.Board.show_game_over_mask();
		this.show_game_finished_options();
		
		this.show_anim_text(LANG.txt_draw);
	}
	
	this.show_game_finished_options = function()
	{
		$('#game_finished_options').show();
	}

	this.show_anim_text = function(text)
	{
		if($('.effect_txt').length == 0)
			// this.Helper.createChars(text, {mid_x: 300, mid_y: 300, text_colour: '#B9D7A0'});
			this.Helper.createChars(text, {mid_x: 300, mid_y: 300, text_colour: this.Board.colours.wtile});
		
		TweenLite.set($('#effects'), {perspective: 500});
		
		var chars = $('.effect_txt');
		var charsS = $('.effect_txtS');
		
		tl = new TimelineMax({repeat: 1, repeatDelay: 2, yoyo:true, onComplete: this.clear_anim_text});
		tl.staggerFrom(chars, 0.4, {alpha: 0}, 0.06, "stage1");
		tl.staggerFrom(chars, 0.8, {rotationY: "-270deg", top: 80, transformOrigin: "50% 50% -40", ease: Back.easeOut}, 0.06, "stage1");
		tl.staggerFrom(charsS, 0.4, {alpha: 0}, 0.06, "stage1");
		tl.staggerFrom(charsS, 0.8, {rotationY: "-270deg", top: 80, transformOrigin: "50% 50% -40", ease: Back.easeOut}, 0.06, "stage1");
		// tl.staggerTo(chars, 0.4, {rotationX: "360deg", color: '#B9D7A0', transformOrigin: "50% 50% 10"}, 0.02, 'stage2');
		tl.staggerTo(chars, 0.4, {rotationX: "360deg", color: this.Board.colours.wtile, transformOrigin: "50% 50% 10"}, 0.02, 'stage2');
		tl.staggerTo(charsS, 0.4, {rotationX: "360deg", color: "black", transformOrigin: "50% 50% 10"}, 0.02, 'stage2');
		
	}
	// remove this when testing is done.
	this.clear_anim_text = function()
	{
		$('#effects').empty();
	}
	
	this.init_time_remaining = function(data)
	{
		var started = $(data).find('TIME_STARTED');
		// this.timeleft.started = parseInt(started.text());
		// this.timeleft.duration = parseInt($(data).find('TIME_DURATION').text());
		// this.update_time_remaining();

		this.Clock = new ChessClock({
			w_clock_display: $('#clock_w'),
			b_clock_display: $('#clock_b'),
			w_time_left: parseInt($(data).find('TIME_W_LEFT').text()),
			b_time_left: parseInt($(data).find('TIME_B_LEFT').text()),
			turn: this.turn,
			side: this.side,
			update_interval: 2000,
			w_time_allowed: parseInt($(data).find('TIME_W_ALLOWED').text()),
			b_time_allowed: parseInt($(data).find('TIME_B_ALLOWED').text())
		});
	}
	
}


function Helper()
{
	// Returns array containing the tile coords of chess pieces. Tile coords are 0 based.
	this.get_pieces_from_FEN = function(fen)
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
						pieces.push({x: x, y: y, type: PIECE_TYPE.bKing});
						x++;
						break;
					case 'q':
						pieces.push({x: x, y: y, type: PIECE_TYPE.bQueen});
						x++;
						break;
					case 'b':
						pieces.push({x: x, y: y, type: PIECE_TYPE.bBishop});
						x++;
						break;
					case 'n':
						pieces.push({x: x, y: y, type: PIECE_TYPE.bKnight});
						x++;
						break;
					case 'r':
						pieces.push({x: x, y: y, type: PIECE_TYPE.bRook});
						x++;
						break;
					case 'p':
						pieces.push({x: x, y: y, type: PIECE_TYPE.bPawn});
						x++;
						break;
					case 'K':
						pieces.push({x: x, y: y, type: PIECE_TYPE.wKing});
						x++;
						break;
					case 'Q':
						pieces.push({x: x, y: y, type: PIECE_TYPE.wQueen});
						x++;
						break;
					case 'B':
						pieces.push({x: x, y: y, type: PIECE_TYPE.wBishop});
						x++;
						break;
					case 'N':
						pieces.push({x: x, y: y, type: PIECE_TYPE.wKnight});
						x++;
						break;
					case 'R':
						pieces.push({x: x, y: y, type: PIECE_TYPE.wRook});
						x++;
						break;
					case 'P':
						pieces.push({x: x, y: y, type: PIECE_TYPE.wPawn});
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
	}
	
	this.get_side_from_piece = function(type)
	{
	//console.log(type <= PIECE_TYPE.wPawn ? SIDE.white : SIDE.black);
		return type <= PIECE_TYPE.wPawn ? SIDE.white : SIDE.black;
	}
	
	this.get_general_type_of_piece = function(type)
	{
		return (type <= PIECE_TYPE.wPawn ? type : type - 6);
	}
	
	// Converts a tile index to a tile coord string (0 = 'a1', 63 = 'h8').
	this.index_to_coord = function(index)
	{
		var files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
		return files[index % 8] + (Math.floor(index / 8) + 1);
	}
	
	// Converts a tile coord string to a tile index ('a1' = 0, 'h8' = 63).
	this.coord_to_index = function(index)
	{
		var files = {a: 0, b: 1, c: 2, d: 3, e: 4, f: 5, g: 6, h: 7};
		return (index[1] - 1) * 8 + files[index[0]];
	}

	this.createChars = function(phrase, opts) {
		var sentence = phrase.split("");
		var curlen = 0;
		$.each(sentence, function(index, val) {
			if(val === " "){
				val = "&nbsp;";
			}
			var letterS = $("<span/>", {
						id : "txt" + index
			}).addClass('effect_txtS').html(val).appendTo($('#effects'));
			$(letterS).css({
				left: curlen + 5,
				top: '50px',
				font: '50px verdana',
				position: 'absolute',
				color: 'black',
				'font-weight': 'bold'
			});
			var letter = $("<span/>", {
						id : "txtS" + index
			}).addClass('effect_txt').html(val).appendTo($('#effects'));
			$(letter).css({
				left: curlen, 
				top: '45px',
				font: '50px verdana',
				position: 'absolute',
				color: opts.text_colour,
				'font-weight': 'bold'
			});
			
			curlen += $(letter).width();
		});
	}
	this.zero_pad = function(number, places)
	{
		var value = String(number);
		var add = places - value.length;
		if(add > 0)
		{
			for(var i = 0; i < add; i++)
				value = '0' + value;
		}
		return value;
	}
}

function PGNParser()
{
	this.parse = function(pgn)
	{
		var parts = {};
		var in_tag = false;
		var in_quotes = false;
		var in_key = false;
		var tag_name = '';
		var tag_value = '';
		var movetext = '';
		var prv_char = '';
		for(var c = 0; c < pgn.length; c++)
		{
			if(in_tag == false)
			{
				if(pgn[c] == '[')
				{
					in_tag = true;
					in_key = true;
					tag_name = '';
				}
				else if(pgn[c] != '\n' && pgn[c] != '\r')
				{
					movetext += pgn[c];
				}
			}
			else
			{
				if(pgn[c] == ']' && in_quotes == false)
				{
					in_tag = false;
					parts[tag_name] = tag_value;
				}
				else if(in_key)
				{
					if(pgn[c] == ' ')
						in_key = false;
					else
						tag_name += pgn[c];
				}
				else
				{
					if(in_quotes == false && pgn[c] == '"')
					{
						in_quotes = true;
						prv_char = '"';
						tag_value = '';
					}
					else if(in_quotes)
					{
						if(pgn[c] == '"' && prv_char != '\\')
						{
							in_quotes = false;
						}
						else
						{
							tag_value += pgn[c];
							prv_char = pgn[c];
						}
					}
				}
			}
		}
		parts['movetext'] = movetext;
		return parts;
	}
	
}

function ChessClock(settings)
{
// console.log(settings);
	var white_time_left = settings.w_time_left;
	var black_time_left = settings.b_time_left;
	var turn = settings.turn;
	var side = settings.side;
	var white_time_allowed = settings.w_time_allowed;
	var black_time_allowed = settings.b_time_allowed;
	var update_interval = settings.update_interval;
	
	var move_started = null;
	var update_checker;
	var clock_tick_id = null;
	var running = false;
	
	this.Helper = new Helper();
	var self = this;


	var els = {
		w_clock_display: settings.w_clock_display,
		b_clock_display: settings.b_clock_display
	};


	//console.log('ready');
	els.w_clock_display.html(make_descriptive_time(white_time_left));
	els.b_clock_display.html(make_descriptive_time(black_time_left));
	if(turn == SIDE.white)
	{
		els.w_clock_display.addClass('clock_active');
		els.b_clock_display.removeClass('clock_active');
	}
	else
	{
		els.b_clock_display.addClass('clock_active');
		els.w_clock_display.removeClass('clock_active');
	}

	//$('#change_turn').click(change_turn);
	if(turn != side)
	{
		//$('#change_turn').hide();
		//update_checker = setTimeout(get_update, update_interval);
	}

	//move_started = new Date();
	//clock_tick();
	//clock_tick_id = setInterval(clock_tick, 1000);


	
	this.changed_turn = function(data)
	{
		// try
		// {
			// data = $.parseJSON(data);
		// }catch(ex){
			// alert('Error changing turn: ' . ex);
			// return;
		// }
		
		// console.log('changed turn', data);
		move_started = new Date();
		white_time_left = data.correct_time.white;
		black_time_left = data.correct_time.black;
		// console.log('left:', white_time_left, black_time_left);
		els.w_clock_display.html(make_descriptive_time(white_time_left));
		els.b_clock_display.html(make_descriptive_time(black_time_left));
		turn = (turn == SIDE.white ? SIDE.black : SIDE.white);
		if(turn == SIDE.white)
		{
			els.w_clock_display.addClass('clock_active');
			els.b_clock_display.removeClass('clock_active');
		}
		else
		{
			els.b_clock_display.addClass('clock_active');
			els.w_clock_display.removeClass('clock_active');
		}

		if(turn != side)
		{
			//$('#change_turn').hide();
			//update_checker = setTimeout(get_update, update_interval);
		}
		// else
		// {
			//$('#change_turn').show();
		// }
		// $('#current_turn_indicator').html(turn == side ? 'It is YOUR turn' : 'It is NOT YOUR turn');
	}

	function clock_tick()
	{
		var now = new Date();
		var timediff = now.getTime() - move_started.getTime();
		var t = 0;
		timediff /= 1000;
		timediff = Math.round(timediff);
		if(turn == SIDE.white)
		{
			t = white_time_left - timediff;
			if(t < 0) t = 0;
			//console.log(t, time_allowed - timediff, now, move_started);
			els.w_clock_display.html(make_descriptive_time(t));
		}
		else
		{
			t = black_time_left - timediff;
			if(t < 0) t = 0;
			els.b_clock_display.html(make_descriptive_time(t));
		}
	}

	this.start_clock = function()
	{
		if(running) return;
		move_started = new Date();
		running = true;
		clock_tick_id = setInterval(clock_tick, 1000)
	}
	
	this.stop_clock = function()
	{
		if(!self.running) return;
		self.running = false;
		clearInterval(self.clock_tick_id);
		self.clock_tick_id = null;
	}
	
	function make_descriptive_time(seconds)
	{
		var text = "";
		var d = 0; var h = 0; var m = 0; var s = 0;
		d = Math.floor(seconds / (3600 * 24));
		h = Math.floor((seconds - d * 3600 * 24) / 3600);
		m = Math.floor((seconds - d * 3600 * 24 - h * 3600) / 60);
		s = Math.floor(seconds - d * 3600 * 24 - h * 3600 - m * 60);

		LANG.txt_seconds_symbol = 'S';
		LANG.txt_minutes_symbol = 'M';
		LANG.txt_hours_symbol = 'H';
		LANG.txt_days_symbol = 'D';
		
		if(d > 0)
			// text = LANG.txt_days_remaining.replace("{d}", d);
			text = d + LANG.txt_days_symbol + ' ' + h + LANG.txt_hours_symbol;
		else
		{
			if(h > 0)
				//text = LANG.txt_time_remaining1.replace("{h}", self.Helper.zero_pad(h, 2)).replace("{m}", self.Helper.zero_pad(m, 2));
				text = h + LANG.txt_hours_symbol + ' ' + m + LANG.txt_minutes_symbol;
			else
				//text = LANG.txt_time_remaining2.replace("{m}", self.Helper.zero_pad(m, 2)).replace("{s}", self.Helper.zero_pad(s, 2));
				text = m  + LANG.txt_minutes_symbol + ' ' + s + LANG.txt_seconds_symbol;
		}
		return text;
	}
}