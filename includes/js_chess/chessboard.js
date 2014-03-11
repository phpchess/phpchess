function ChessBoard()
{
	var self = this;
	
	this.els = {
		container: undefined,			// Where all DOM elements for the board will be put
		border_top: undefined,			// The top border element
		border_bottom: undefined,		// bottom border element
		border_left: undefined,			// left border element
		border_right: undefined,		// right border element
		main: undefined,				// element containing the board tiles
		from_tile_marker: undefined,	// element indicating the 'from' tile of the previous move
		to_tile_marker: undefined,		// element indicating the 'to' tile of the previous move
		highlight_tile: undefined,		// element indicating the tile being currently highlited by the user
		start_tile: undefined,			// element indicating the source tile clicked on by the user 
		board_mask: undefined,			// element applied to cover the board when moving is not possible
		board_mask2: undefined,			// element applied to cover the board when the game is over
		promotion_dialog: undefined		// element for a dialog to allow selecting a piece promotion 
	};
	
	this.tile_width = 0;
	this.border_width = 0;
	this.side;
	this.turn;
	this.colours = {btile: '#666', wtile: '#FFF', highlighted_tile: '#0F0', prv_move: '#F60', stile: '#0F0'};
	this.set = {location: './modules/RealTimeInterface/img_chess/', white_files: ['wkw.gif', 'wqw.gif', 'wbw.gif', 'wnw.gif', 'wrw.gif', 'wpw.gif'], black_files: ['bkw.gif', 'bqw.gif', 'bbw.gif', 'bnw.gif', 'brw.gif', 'bpw.gif'] };
	this.markers = {location: './modules/RealTimeInterface/images/', white: 'blue_circle.png', black: 'red_circle.png'};
	
	this.tiles_status = {on_tile: -1, selected_start: -1, selected_end: -1};
	this.tiles = [];					// NOT USED FOR ANYTHING!!!
	this.pieces = [];					// 64 element of ChessConst.PIECE_TYPE. First element is A1, last H8.
	
	this.is_masked = false;
	this.LANG = {txt_promotion: 'Promotion'};
	
	// Board elements are given z-index values so they appear in the correct order.
	this.zindex = {
		board_lvl: 0,			// The border and tiles (makes up the board)
		tile_marker_lvl: 1,		// Markers placed over tiles (previous move markers for example)
		piece_lvl: 2,			// The chess pieces
		mask_lvl: 3				// anything above the pieces (current tile highlighter, board masks and promotion dialog)
	};
	
	// Callbacks
	this.moved_piece = undefined;
	this.promoted = undefined;
	
	
	// 'Public' Functions:
	
	// Sets up the chess board. Settings include:
	// The DOM element that the board will be put in.
	// pieces - array of pieces to place on board.
	// side - orientation of the board. If SIDE.white, rank 1 is at the bottom. If SIDE.black, rank 8 is at the bottom.
	// turn - the player whose turn it is.
	// width - the width of tiles. Default 50.
	// border -	the width of the border. Default 20.
	// colours - the colours to use for the board.
	// set - the location and file names for the chess pieces.
	this.initialise = function(settings)
	{
		this.side = settings.side;
		this.tile_width = (settings.width !== undefined ? settings.width : 50);
		this.border_width = (settings.border_width !== undefined ? settings.border_width : 20);
		this.turn = settings.turn;
		this.els.container = settings.container;
		
		if(settings.colours) this.colours = settings.colours;
		if(settings.set) this.set = settings.set;
		if(settings.LANG) this.LANG = settings.LANG;
		
		this._create_board();
		
		this._place_pieces(settings.pieces);
		
		this._create_promotion_dialog();
		
		this._add_event_handlers();
	}
	
	/* setup pieces
		takes array of piece position and type objects
		call _clear_board
		call _place_pieces()
	
	*/
	
	/* setup from fen 
		takes a fen string
		get pieces component and player turn component
		set current turn
		call _clear_board()
		call _get_pieces_from_FEN() to get pieces
		call _place_pieces()
	*/
	
	/* clear_board
		clears board pieces, tile markers, highlighted tile
	
	*/
	
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
		console.log('changing to type: ', type);
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
	
	
	// 'Private' Functions:
	
	// Create all board elements (except for the chess pieces)
	this._create_board = function()
	{
		this._create_border_elements();
		this._create_tiles();
		this._create_markers();
		this._create_board_mask();
		this._create_board_mask2();
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
		
		// Work out font scaling value to use so that the file and rank characters fit within the board border.
		var info = this._get_fontsize();
		var max = info.w > info.h ? info.w : info.h;
		var fontsize = '';
		var scale = (this.border_width - 2) / max;
		fontsize = Math.round(scale * info.size * 100) / 100 + info.unit;
		top.css('font-size', fontsize);
		left.css('font-size', fontsize);
		right.css('font-size', fontsize);
		bottom.css('font-size', fontsize);

		var total_width = this.tile_width * 8 + this.border_width * 2;
		
		var files = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
		var ranks = ['8', '7', '6', '5', '4', '3', '2', '1'];
		if(this.side == ChessConst.SIDE.black)
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
		var y = this.border_width / 2;	// It is hard to vertical align text. Therefore applied an offset instead of just 0.
		for(var i = 0; i < 8; i++)
		{
			left.append('<div class="board_border" style="position: absolute; top: ' + y + 'px; left: 0px; width: ' + this.border_width + 'px; height: ' + this.tile_width + 'px">' + ranks[i] + '</div>');
			y += this.tile_width;
		}
		
		// Generate 8 divs for the right border that will contain the ranks (1 through 8).
		right.css({position: 'absolute', left: total_width - this.border_width + 'px', top: this.border_width + 'px', width: this.border_width + 'px', height: this.tile_width * 8 + 'px'}).addClass('board_border');
		var y = this.border_width / 2;	// It is hard to vertical align text. Therefore applied an offset instead of just 0.
		for(var i = 0; i < 8; i++)
		{
			right.append('<div class="board_border" style="position: absolute; top: ' + y + 'px; left: 0px; width: ' + this.border_width + 'px; height: ' + this.tile_width + 'px">' + ranks[i] + '</div>');
			y += this.tile_width;
		}
		
		
	}
	this._create_tiles = function()
	{
		var side = ChessConst.SIDE.black; // (this.side == SIDE.white ? this.colours.wtile : this.colours.btile);
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
					'background-color': (side == ChessConst.SIDE.white ? this.colours.wtile : this.colours.btile),
					'z-index': self.zindex.board_lvl
				});
				this.els.main.append(tile);	
				side = (side == ChessConst.SIDE.white ? ChessConst.SIDE.black :  ChessConst.SIDE.white);
				this.tiles.push({highlighted: false, selected_start: false, selected_end: false});
			}
			side = (side == ChessConst.SIDE.white ? ChessConst.SIDE.black : ChessConst.SIDE.white);
		}
	}
	this._create_markers = function()
	{
		this.els.from_tile_marker = $('<div id="from_tile_marker"></div>');
		this.els.from_tile_marker.css({position: 'absolute', left: -9999, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.prv_move, 'z-index': self.zindex.tile_marker_lvl});
		this.els.container.append(this.els.from_tile_marker);
		
		this.els.to_tile_marker = $('<div id="to_tile_marker"></div>');
		this.els.to_tile_marker.css({position: 'absolute', left: -9999, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.prv_move, 'z-index': self.zindex.tile_marker_lvl});
		this.els.container.append(this.els.to_tile_marker);

		this.els.highlight_tile = $('<div id="highlight_tile"></div>');
		this.els.highlight_tile.css({position: 'absolute', left: -9999, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.highlighted_tile, cursor: 'pointer', 'z-index': self.zindex.mask_lvl});
		this.els.container.append(this.els.highlight_tile);
		
		this.els.start_tile = $('<div id="start_tile"></div>');
		this.els.start_tile.css({position: 'absolute', left: 0, height: this.tile_width - 6, width: this.tile_width - 6, display: 'none', border: '3px solid ' + this.colours.stile, 'z-index': self.zindex.mask_lvl});
		this.els.container.append(this.els.start_tile);
	}
	this._create_board_mask = function()
	{
		var el = $('<div id="board_mask"></div>');
		var board_size = self.tile_width * 8 + self.border_width * 2;
		el.css({
			position: 'absolute', width: board_size, height: board_size, 'background-color': '#888', opacity: 0.6, filter: 'alpha(opacity=60)', display: 'none'
		});
		self.els.container.append(el);
		self.els.board_mask = el;
	}
	this._create_board_mask2 = function()
	{
		var el = $('<div id="board_mask2"></div>');
		var board_size = self.tile_width * 8 + self.border_width * 2;
		el.css({
			position: 'absolute', width: board_size, height: board_size, 'background-color': '#888', opacity: 0.1, filter: 'alpha(opacity=10)', display: 'none'
		});
		self.els.container.append(el);
		self.els.board_mask2 = el;
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
			var url = this.set.location + (piece.type <= ChessConst.PIECE_TYPE.wPawn ? this.set.white_files[piece.type - 1] : this.set.black_files[piece.type - 7]);
			el.css({position: 'absolute', left: pos.x + 'px', top: pos.y + 'px', width: this.tile_width, height: this.tile_width, 'z-index': self.zindex.piece_lvl});
			el.attr('src', url);
			this.els.container.append(el);
			this.pieces[piece.y * 8 + piece.x] = {type: piece.type, el: el};
		}
		//console.log(this.pieces);
	}
	
	this._create_promotion_dialog = function()
	{
		var width = self.tile_width;
		var files = (self.side == ChessConst.SIDE.white ? self.set.white_files : self.set.black_files);
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
		}
		$(this.els.container).click(function(e){self._clicked_mouse(self, e)});
		$('.promotion_item').click(function(e){
			self.moved_piece(self.tiles_status.selected_start, self.tiles_status.selected_end, parseInt($(this).attr('piece'), 10));
			e.stopPropagation();		// Don't want the click to be handled by the container element.
		});
	}
	
	// Converts a tile index to a x,y position on the board.
	// index: the tile index (0 to 63)
	// location: can define to what position the tile should resolve to, since the tile occupies an area.
	//           Can choose from 'top-left' or 'center'. Default is 'top-left'.
	this._index_to_pos = function(index, location)
	{
		var rank, file, x, y;
		location = (location !== undefined ? 'center' : location);
		
		rank = Math.floor(index / 8);
		file = index % 8;
		if(this.side == ChessConst.SIDE.white)
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
		
		if(this.side == ChessConst.SIDE.white)
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
			board._changed_mouse_over_tile(i, board.tiles_status.on_tile);
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
			if(board.pieces[cur] != undefined && ChessHelper.get_side_from_piece(board.pieces[cur].type) == board.turn)
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
			if(board.pieces[cur] == undefined || ChessHelper.get_side_from_piece(board.pieces[cur].type) != board.turn)
			{
				if(board.moved_piece !== undefined)
				{
					//board.tiles_status.selected_end = cur;
					var ret = board.moved_piece(board.tiles_status.selected_start, cur);
					console.log('ret = ' + ret);
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
	
	this._changed_mouse_over_tile = function(cur, old)
	{
	//console.log(cur, old);
		this.tiles_status.on_tile = cur;
		// If the user has not selected a tile then highlight tile over if it has one of the current player's pieces.
		// Otherwise only highlight empty tiles or tiles with pieces of the other player.
		if(this.tiles_status.selected_start == -1)
		{
			if(this.pieces[cur] == undefined || ChessHelper.get_side_from_piece(this.pieces[cur].type) != this.turn)
				this._position_highlight(-1);
			else
				this._position_highlight(cur);
		}
		else
		{
			if(this.pieces[cur] == undefined || ChessHelper.get_side_from_piece(this.pieces[cur].type) != this.turn)
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
		var file = (self.turn == ChessConst.SIDE.white ? self.markers.white : self.markers.black);
		var pos = self._index_to_pos(index);
		el.show();
		el.css({left: pos.x, top: pos.y});
	}
	
	this._position_to_tile_marker = function(index)
	{
		var el = self.els.to_tile_marker;
		var file = (self.turn == ChessConst.SIDE.white ? self.markers.white : self.markers.black);
		var pos = self._index_to_pos(index);
		el.show();
		el.css({left: pos.x, top: pos.y});
	}
	
	//todo:
	// clear_board
	// setup_board
	// setup should call clear_board and setup_board
	
	// Obtains the size (width, height) of the letter A as well as the font size and the unit used.
	this._get_fontsize = function()
	{
		var el = $('<span>A</span>');
		self.els.container.append(el);
		var width = el.width();
		var height = el.height();
		var res = /(\d+)(\D*)/.exec(el.css('font-size'));
		el.remove();
		return {w: width, h: height, size: res[1], unit: res[2]};
	}

	// Returns array containing the tile coords of chess pieces. Tile coords are 0 based.
	this._get_pieces_from_FEN = function(fen)
	{
		var spaces = {'1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8};
		var PT = ChessConst.PIECE_TYPE;
		var piece_types = {
			'k': PT.bKing, 'q': PT.bQueen, 'b': PT.bBishop, 'n': PT.bKnight, 'r': PT.bRook, 'p': PT.bPawn, 
			'K': PT.wKing, 'Q': PT.wQueen, 'B': PT.wBishop, 'N': PT.wKnight, 'R': PT.wRook, 'P': PT.wPawn
		};
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
					case 'q':
					case 'b':
					case 'n':
					case 'r':
					case 'p':
					case 'K':
					case 'Q':
					case 'B':
					case 'N':
					case 'R':
					case 'P':
						pieces.push({x: x, y: y, type: piece_types[chr]});
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
}