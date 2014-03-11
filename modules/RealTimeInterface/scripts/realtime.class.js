/**************************************************************************
*	Changed
**************************************************************************/

//variables that hold string data retrieved from the language file
var lostgame, wongame, gamedraw, connectionproblem, draw, resign, revoke, warndraw, warnresign, warnrevoke;

//variables to set piece type
var pawn=0, rook=1, knight=2, bishop=3, queen=4, king=5;

//variables to set piece and player colors
var white=1, black=2;

//variable that holds piece width
var w=50;

//variable that holds browser type
var ie=false;

//global variables used by a few functions to set the position of pieces
var offx=0, offy=0;

//default player type is white
var player=white;

//array to hold board letters
var letters=['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
//corresponding integer values for letters
var a=0, b=1, c=2, d=3, e=4, f=5, g=6, h=7;

//corresponding integer values for rows
var r1=0, r2=1, r3=2, r4=3, r5=4, r6=5, r7=6, r8=7;

//main objects used by all the pieces for collaboration
var divWrapper, divBoard, cells, pieces;

//variable to hold the current turn
var turn;

//arrays to store colors for each cells. 0 if no piece is on the cells
var board = new Array();
// array of the chess pieces for each side. pieces[0] = white pieces, pieces[1] = black pieces.
var pieces= new Array();

//variables that keep a reference to last element used and session id
var lastpiece, oldpiece, sessionid;

//variables that are used to define the current state
var init=false, direct=true, processing=false, checkmove=false, castling=false, errorcount=0;

//chat update frequency (in seconds).
var chatfrequency = 30;
// Frequency of last move checks (in seconds).
var last_move_check_frequency = 10;

//any suffix to the move made. only used for promotion
var movesuffix='';

// Need two divs to represent the start and end tiles. They are placed on the board as needed.
var start_tile_div, end_tile_div;
// These store the positions of the tiles. If not positioned, then the values are "".
var start_tile_pos = '', end_tile_pos = '';
// The board div. Used to get the position.
var board_div;
// Game status
var game_status = 'playing';



function increaseError(){
	errorcount+=5;
	if(errorcount>10){
		document.getElementById('warning').style.visibility='visible';
	}
}

function decreaseError(){
	if(errorcount>0){
		errorcount--;
	}
	if(errorcount<=10){
		document.getElementById('warning').style.visibility='hidden';
	}
}

function initChat(frequency){
	chatfrequency = frequency;
	var url = 'mobile.php?action=getgamechat&sid='+sessionid+'&gameid='+gameid;
	$.get(url, function(data){ decreaseError(); updateChat(data) }).error(function(){increaseError(); requestChat()});
}

function requestChat(){
	var url='mobile.php?action=getgamechat&sid='+sessionid+'&gameid='+gameid;
	$.get(url, function(data){ decreaseError(); updateChat(data) }).error(function(){increaseError(); requestChat()});
}

function updateChat(transport){
	var i, cb, msglist, msg='';
	
	window.setTimeout('requestChat()', chatfrequency * 1000);
	
	cb = $('#chatbox');
	if(transport.getElementsByTagName('MSG').item(0).firstChild != null)
	{
		msglist=transport.getElementsByTagName('MSG').item(0).firstChild.data.split('\n');
		for(i = 0; i < msglist.length; i++)
		{
			if(msglist[i]!='')
			{
				msglist[i]=msglist[i].replace(/<(\w+)>/, "<strong>&lt;$1&gt;</strong>");
				msg=msg+msglist[i]+'<br>';
			}
		}
		cb.html(msg);
		//cb.scrollTop = cb.scrollHeight;
	}

}

function sendGameMsg(inid){
	var ib=document.getElementById(inid);
	var url='mobile.php?action=sendgamechat&sid='+sessionid+'&gameid='+gameid+'&msg='+escape(ib.value);
	ib.value="";
	$.get(url, function(data){ decreaseError(); }).error(function(){increaseError();});
}

function processPGN(transport){
	// Extract just the moves from the PGN.
	var pgn=transport.getElementsByTagName('PGN').item(0).firstChild.data;
	pgn=pgn.replace(/\[\w*\W*\w*\W*\w*\W*\]/g, "");
	var moves = pgn.split(/\s+[0-9]+\./);
	console.log(moves);
	var move_tbl = '<table>';
	var move_cnt = 1;
	for(var i = 0; i < moves.length; i++)
	{
		if(moves[i] != '')
		{
			var parts = moves[i].split(' ');
			console.log(parts);
			move_tbl += '<tr><td>' + move_cnt + '.</td><td>' + parts[1] + '</td><td>' + parts[2] + '</td></tr>';
			move_cnt++;
		}
	}
	move_tbl += '</table>';
	pgn=pgn.replace(/(\w*\.[0-9]*)/g, "\n<br>$1");
	console.log(move_tbl);
	$('#pgntext').html(move_tbl);
	
	// Update the captured pieces lists.
	var bpiece_map = {P: 'wpw.gif', R: 'wrw.gif', N: 'wnw.gif', B: 'wbw.gif', Q: 'wqw.gif', K: 'wkw.gif'};
	var wpiece_map = {P: 'bpw.gif', R: 'brw.gif', N: 'bnw.gif', B: 'bbw.gif', Q: 'bqw.gif', K: 'bkw.gif'};

	var html = '';
	var taken = transport.getElementsByTagName('CAPTURED_BY_WHITE').item(0).firstChild.data;
	var pieces = taken.split(', ');
	for(var i = 0; i < pieces.length; i++)
	{
		html += '<img src="modules/RealTimeInterface/img_chess/' + wpiece_map[pieces[i]] + '" width="30px" />';
	}
	$('#taken_pieces_white').html(html);
	var taken = transport.getElementsByTagName('CAPTURED_BY_BLACK').item(0).firstChild.data;
	html = '';
	var pieces = taken.split(', ');
	for(var i = 0; i < pieces.length; i++)
	{
		html += '<img src="modules/RealTimeInterface/img_chess/' + bpiece_map[pieces[i]] + '" width="30px" />';
	}
	$('#taken_pieces_black').html(html);
}

function requestPGN(){
	var url='mobile.php?action=get_full_game_update&sid='+sessionid+'&gameid='+gameid;
	$.get(url, function(data){ decreaseError(); processPGN(data)}).error(function(){increaseError(); requestPGN();});
}

function acceptDraw(d){
	if(confirm('Are you sure you want to accept the draw?')){
		var url='mobile.php?action=drawgame&sid='+sessionid+'&gameid='+gameid;
		d.onclick='';
		$.get(url, function(data){ decreaseError(); moveRequest()}).error(function(){increaseError(); acceptDraw();});
		disableDragging();
	}
}

function requestDraw(d){
	if(confirm(warndraw)){
		var url='mobile.php?action=drawgame&sid='+sessionid+'&gameid='+gameid;
		d.onclick='';
		$.get(url, function(data){ 
			decreaseError();
			d.innerHTML = revoke;
			d.onclick = new Function("revokeDraw(this)");
		}).error(function(){increaseError(); requestDraw(d);});
	}
}

function revokeDraw(d){
	if(confirm(warnrevoke)){
		var url='mobile.php?action=revokedrawgame&sid='+sessionid+'&gameid='+gameid;
		d.onclick='';
		$.get(url, function(data){ 
			decreaseError();
			d.innerHTML = draw;
			d.onclick = new Function("requestDraw(this)");
		}).error(function(){increaseError(); revokeDraw(d);});
	}
}

function resignGame(d){
	if(confirm(warnresign)){
		d.onclick='';
		var url='mobile.php?action=resigngame&sid='+sessionid+'&gameid='+gameid;
		$.get(url, function(data){ decreaseError(); moveRequest()}).error(function(){increaseError(); resignGame(d);});
		disableDragging();
	}
}

function initBoard(p, t){
	player = p;
	turn = (player == t);
	checkmove = !turn;
	signal_player_turn();
	if(player==white){
		a=0;
		b=1;
		c=2;
		d=3;
		e=4;
		f=5;
		g=6;
		h=7;
		r1=1;
		r2=2;
		r3=3;
		r4=4;
		r5=5;
		r6=6;
		r7=7;
		r8=8;
	}else{
		a=7;
		b=6;
		c=5;
		d=4;
		e=3;
		f=2;
		g=1;
		h=0;
		r1=8;
		r2=7;
		r3=6;
		r4=5;
		r5=4;
		r6=3;
		r7=2;
		r8=1;
	}
}

function addBoardEvents(board_element)
{
	$(board_element).mousemove(mousemove_on_board).click(mouseclick_on_board).mouseleave(mouseleft_board);
	board_div = board_element;
	$('#game_finished_msg').hide();
}

function mousemove_on_board(event)
{
	if(game_status != 'playing') return;
	var board_pos = $(board_div).offset();
	x = Math.floor((event.pageX - board_pos.left) / w);
	y = 7 - Math.floor((event.pageY - board_pos.top) / w);
	
	// Position either the start or end tile.
	var tile = start_tile_pos == '' ? start_tile_div : end_tile_div;
	var size = w;
	if(!ie)
	{
		size -= 4;	// Non IE browsers don't include the border width in the element size.
	}
	$(tile).css({width: size, height: size, left: x * 50, top: (7 - y) * 50});
	$(tile).show();
}

function mouseclick_on_board(event)
{
	if(game_status != 'playing') return;
	// Get the tile clicked.
	var board_pos = $(board_div).offset();
	x = Math.floor((event.pageX - board_pos.left) / w);
	y = 7 - Math.floor((event.pageY - board_pos.top) / w);

	if(start_tile_pos == '')	// No piece yet selected.
	{
		// Can only select a tile that has a piece on it.
		if(board[x * 8 + y] != undefined)
		{
			start_tile_pos = {x: x, y: y};
		}
	}
	else	// Piece already selected. 
	{
		if(start_tile_pos.x == x && start_tile_pos.y == y)	// Deselected piece.
		{
			start_tile_pos = '';
			$(end_tile_div).hide();
		}
		else	// Made move. Find the correct piece to call setLocation on which will send the move.
		{
			for(var i = 0; i < pieces[0].length; i++)
			{
				if(pieces[0][i].x == start_tile_pos.x && pieces[0][i].y == start_tile_pos.y)
				{
					if(pieces[0][i].checkMove(x, y))
						pieces[0][i].setLocation(x, y);
					break;
				}
			}
			for(var i = 0; i < pieces[1].length; i++)
			{
				if(pieces[1][i].x == start_tile_pos.x && pieces[1][i].y == start_tile_pos.y)
				{
					if(pieces[1][i].checkMove(x, y))
						pieces[1][i].setLocation(x, y);
					break;
				}
			}
			start_tile_pos = '';
			$(start_tile_div).hide();
			$(end_tile_div).hide();
		}
	}
}

function mouseleft_board(event)
{
	if(start_tile_pos == '')
	{
		$(start_tile_div).hide();
	}
	else
	{
		$(end_tile_div).hide();
	}
}

if(navigator.appName.indexOf('Microsoft')>-1){
		ie=true;
}

function getIndex(r){
	switch(r){
		case 'a':
			return 0;
			break;
		case 'b':
			return 1;
			break;
		case 'c':
			return 2;
			break;
		case 'd':
			return 3;
			break;
		case 'e':
			return 4;
			break;
		case 'f':
			return 5;
			break;
		case 'g':
			return 6;
			break;
		case 'h':
			return 7;
			break;
	}
}

//variables to hold the draw request and turn status
var drawrequest=false, turn=true;

function checkMove(transport){

	if(transport.getElementsByTagName('ERROR').length>0){
		alert(connectionproblem);
	}else{
	
		var draw;
		draw=transport.getElementsByTagName('DRAWCODE').item(0).firstChild.data;

		if(draw=='IDS_DRAW_REQUESTED'){
			document.getElementById('draw').innerHTML='%Accept Draw%';
			document.getElementById('draw').onclick=new Function("acceptDraw(this)");
			drawrequest=true;
			//alert(draw);
		}else if(drawrequest){
			document.getElementById('draw').innerHTML='%Request Draw%';
			document.getElementById('draw').onclick=new Function("requestDraw(this)");
			drawrequest=false;
		}else if(draw=='IDS_USER_REQUESTED_DRAW'){
			document.getElementById('draw').innerHTML='%Revoke Draw%';
			document.getElementById('draw').onclick=new Function("revokeDraw(this)");
		}else if(draw=='IDS_DRAW'){
			//alert(gamedraw);
			parent.location='chess_view_games_rt.php';
			$('#game_finished_msg') =  '%[It is a draw]%';
			game_status = 'finished';
			return;
		}
		var move;
		move=transport.getElementsByTagName('MOVECOMMA').item(0).firstChild.data;
		if(move.length>4)
		{
      // Get start/end positions from move received. p is for the promotion tile.
			var xb, yb, xe, ye, xp, yp;
			xb=getIndex(move.charAt(0));
			xe=getIndex(move.charAt(3));
			yb=parseInt(move.charAt(1))-1;
			ye=parseInt(move.charAt(4))-1;
      // Adjust board coords for black player's perspective.
			if(player==black)
			{
				xb=7-xb;
				xe=7-xe;
				yb=7-yb;
				ye=7-ye;
			}
      // For promotions.
			if(move.length>6)
			{
				xp=parseInt(move.charAt(7));
				yp=parseInt(move.charAt(6));	
				if(player==black){
					xp=7-xp;
					yp=7-yp;
				}
        // If promotion tile not empty then remove piece on that tile.
				if(cells[xp*8+yp]!=0){
					cells[xp*8+yp].destroy();	
				}
			}
      // Start tile for move needs to have a piece on it
			if(cells[xb*8+yb]!=0)
			{
        // that belongs to the opposite player.
				if(cells[xb*8+yb].color!=player)
				{
					window.focus();
					if(!ie){
						self.focus();
					}
          // For promotions make sure the moved piece is a pawn. Then do the same thing as
          // already done?
					if(move.length>6 && cells[xb*8+yb].type==pawn){
						xp=parseInt(move.charAt(7));
						yp=parseInt(move.charAt(6));	
						if(player==black){
							xp=7-xp;
							yp=7-yp;
						}
						if(cells[xp*8+yp]!=0){
							cells[xp*8+yp].destroy();	
						}
					}
          // Clear end tile of piece.
					if(cells[xe*8+ye]!=0){
						cells[xe*8+ye].destroy();
					}
					direct=false;
          // Move piece to end tile.
					cells[xb*8+yb].setLocation(xe, ye);
          // For promotion moves.
					if(move.length==6){
						var t, p;
						switch(move.charAt(5)){
							case 'Q':
								t=queen;
								break;
							case 'R':
								t=rook;
								break;
							case 'B':
								t=bishop;
								break;
							case 'N':
								t=knight;
								break;
						}
            // Destroy moved piece?
						cells[xe*8+ye].destroy();
            // Create a new piece
						if(player==white){
							p=new Piece(divBoard, t, xe, ye+1, black);
						}else{
							p=new Piece(divBoard, t, xe, ye+1, white);
						}		
					}
					if(cells[xe*8+ye].type==king && Math.abs(xb-xe)==2){
						if(player == black)  // White has castled.
						{
						  if(xb > xe){  // Castling left (ie short)
							xb = 0; xe++;
						  }
						  else{  // Castling right (ie long)
							xb = 7; xe--;
						  }
						}
						else  // Black has castled.
						{
						  if(xb > xe){  // Castling left (ie long)
							xb = 0; xe++;
						  }
						  else{  // Castling right (ie short)
							xb = 7; xe--;
						  }
						}
						cells[xb*8+yb].setLocation(xe, ye);						
					}
					direct=true;
					enableDragging();
					turn=true;
				}
			}
		}
		
		var status, winner;
		status=transport.getElementsByTagName('STATUS').item(0).firstChild.data;
		// console.log('status is ', status);
		if(status=='B' || status=='W'){
			if(status=='B'){
				winner=black;
			}else{
				winner=white;
			}
			// console.log('winner is, ', winner);
			if(winner==player){
				//alert(wongame);
				$('#game_finished_msg').innerHTML = '%You won%';
			}else{
				//alert(lostgame);
				$('#game_finished_msg').innerHTML = '%You lost%';
			}
			$('#game_finished_msg').show();
			//parent.location='chess_view_games_rt.php';
			// console.log('should be shown now');
			document.getElementById('boardblock').style.visibility='hidden';
			game_status = 'finished';
			return;
		}
		if(!turn){
			window.setTimeout('moveRequest()', last_move_check_frequency * 1000);
		}else{
			requestPGN();
		}
	}
	signal_player_turn();
}

function moveRequest(){
	var url='getlastmove.php?sid='+sessionid+'&gameid='+gameid;
	$.get(url, function(data){ decreaseError(); checkMove(data);}).error(function(){increaseError(); moveRequest();});
	turn=false;
}

function findLocation(o){
	offx=o.offsetLeft, offy=o.offsetTop;
		
	while(o=o.offsetParent){
		offx+=o.offsetLeft;
		offy+=o.offsetTop;
	}
}

function enableDragging(){
	document.getElementById('boardblock').style.visibility='hidden';
}

function disableDragging(){
	document.getElementById('boardblock').style.visibility='visible';
}

function processMove(transport){
	if(transport.getElementsByTagName('MOVE').item(0).firstChild.data=='false'){
		var c, temp
		processing=false;
		
		temp=oldpiece;
		c=oldpiece.color;
		
		direct=false;
		lastpiece.setLocation(lastpiece.oldx, lastpiece.oldy);
		board[temp.x*8+temp.y]=c;
		cells[temp.x*8+temp.y]=temp;
		direct=true;
		castling=false;
		enableDragging();
	}else{
		if(oldpiece!=0){
			oldpiece.destroy();
		}	
		processing=false;
		direct=false;
		if(castling && lastpiece.type==king){
			if(lastpiece.x>3){
				cells[8*7+lastpiece.y].setLocation((lastpiece.oldx+lastpiece.x)/2, lastpiece.y);
			}else{
				cells[lastpiece.y].setLocation((lastpiece.oldx+lastpiece.x)/2, lastpiece.y);				
			}
		}
		if(movesuffix!=''){
			var t, src='';
			switch(player){
				case white:
					src=src+'w';
					break;
				case black:
					src=src+'b';
					break;
			}
			
			switch(movesuffix){
				case 'Q':
					lastpiece.type=queen;
					lastpiece.check = lastpiece.checkQueenMove;
					src=src+'qw.gif';
					break;
				case 'R':
					lastpiece.type=rook;
					lastpiece.check = lastpiece.checkRookMove;
					src=src+'rw.gif';
					break;
				case 'B':
					lastpiece.type=bishop;
					lastpiece.check = lastpiece.checkBishopMove;
					src=src+'bw.gif';
					break;
				case 'N':
					lastpiece.type=knight;
					lastpiece.check = lastpiece.checkKnightMove;
					src=src+'nw.gif';
					break;
			}
			
			lastpiece.div.innerHTML='<img src="modules/RealTimeInterface/img_chess/'+src+'" width="'+w+'px" />';
		}
		lastpiece.setLocation(lastpiece.x, lastpiece.y);
		direct=true;
		castling=false;
		moveRequest();	
		requestPGN();
		signal_player_turn();
	}
	movesuffix='';
}

function sendMove(p){
	var url='mobile.php?action=move&sid='+sessionid+'&gameid='+gameid+'&from=';
	if(player==white){
		url=url+(letters[p.oldx]+''+(p.oldy+1))+'&to='+(letters[p.x]+''+(p.y+1));
	}else{
		url=url+(letters[7-p.oldx]+''+(8-p.oldy))+'&to='+(letters[7-p.x]+''+(8-p.y));
	}
	$.get(url + movesuffix, function(data){ decreaseError(); processMove(data); }).error(function(){increaseError();});
	disableDragging();
}

function sendUpgrade(t){
	movesuffix=t;
	sendMove(lastpiece);
	processing=true;
	document.getElementById('prompt').style.visibility='hidden';
	document.getElementById('promptwrapper').style.visibility='hidden';
	document.body.style.overflow="auto";
}

function cancelUpgrade(){
	var x=lastpiece.x, y=lastpiece.y, c, temp;
	document.getElementById('prompt').style.visibility='hidden';
	document.getElementById('promptwrapper').style.visibility='hidden';
	document.body.style.overflow="auto";
	temp=oldpiece;
	c=oldpiece.color;
	direct=false;
	enableDragging();
	lastpiece.setLocation(lastpiece.oldx, lastpiece.oldy);
	board[x*8+y]=c;
	cells[x*8+y]=temp;
	direct=true;
	processing=false;
	
}

function showUpgrade(p){
	document.getElementById('prompt').style.visibility='visible';
	document.getElementById('prompt').style.display='block';
	document.getElementById('promptwrapper').style.visibility='visible';
	document.getElementById('promptwrapper').style.display='block';
	document.body.style.overflow="hidden";
	if(ie){
		document.getElementById('prompt').style.top=Math.floor((document.body.scrollTop+document.body.clientHeight)/2-100);
		document.getElementById('prompt').style.left=Math.floor((document.body.scrollLeft+document.body.clientWidth)/2-100);
		document.getElementById('promptwrapper').style.top=document.body.scrollTop;
		document.getElementById('promptwrapper').style.left=document.body.scrollLeft;
		document.getElementById('promptwrapper').style.width=document.body.clientWidth;
		document.getElementById('promptwrapper').style.height=document.body.clientHeight;
	}else{
		document.getElementById('prompt').style.top=Math.floor((window.pageYOffset+window.innerHeight)/2-100)+'px';
		document.getElementById('prompt').style.left=Math.floor((window.pageXOffset+window.innerWidth)/2-100)+'px';
		document.getElementById('promptwrapper').style.top=window.pageYOffset+'px';
		document.getElementById('promptwrapper').style.left=window.pageXOffset+'px';
		document.getElementById('promptwrapper').style.width=window.innerWidth+'px';
		document.getElementById('promptwrapper').style.height=window.innerHeight+'px';
	}
}

function Piece(d, t, x, y, c){
	var src='img_chess/';
	this.container=d;
	this.div=document.createElement('div');
	this.type=t;
	this.x=x;
	this.y=y-1;
	this.color=c;
	this.w=w;
	this.board=board;
	this.player=player;
	
	cells[x*8+y-1]=this;
	
	switch(this.color){
		case white:
			src=src+'w';
			break;
		case black:
			src=src+'b';
			break;
	}
	
	this.check=function(x, y){
		return true;
	}
				
	switch(this.type){
		case pawn:
			src=src+'pw.gif';
			this.check = this.checkPawnMove;
			break;
		case rook:
			this.check = this.checkRookMove;
			src=src+'rw.gif';
			break;
		case knight:
			this.check = this.checkKnightMove;
			src=src+'nw.gif';
			break;
		case bishop:
			this.check = this.checkBishopMove;
			src=src+'bw.gif';
			break;
		case queen:
			this.check = this.checkQueenMove;
			src=src+'qw.gif';
			break;
		case king:
			this.moved=false;
			this.check = this.checkKingMove;
			src=src+'kw.gif';
			break;
	}
	
	this.div.innerHTML='<img src="modules/RealTimeInterface/'+src+'" width="'+w+'px" />';
	this.div.id='c'+x+''+y;
	this.div.style.position='absolute';
	this.setLocation(this.x, this.y);
	this.setSize(this.w, this.w);
	
	this.container.appendChild(this.div);
		
	if(this.color==player){
		findLocation(this.container);
		
		this.offx=offx;
		this.offy=offy;
		//new Draggable(this.div, {notifyobj:this, onEnd:this.checkDrop});
		
	}

}

Piece.prototype.destroy = function(){
	this.div.style.visibility='hidden';
	this.board[this.x*8+this.y]=0;
	cells[this.x*8+this.y]=0;
}

Piece.prototype.setLocation = function(x, y){
	if(processing){
		return;
	}
	lastpiece=this;
	oldpiece=cells[8*x+y];
	
	this.board[this.x*8+this.y]=0;
	this.board[x*8+y]=this.color;
	this.oldx=this.x;
	this.oldy=this.y;
	this.x=x;
	this.y=y;
	if(init && direct && !processing){
		disableDragging();
		processing=true;
		sendMove(this);
	}
	if(ie){
		this.div.style.left=x*w;
		this.div.style.top=(7-y)*w;
	}else{
		this.div.style.left=x*w+'px';
		this.div.style.top=(7-y)*w+'px';
	}
	
	cells[this.oldx*8+this.oldy]=0;
	cells[this.x*8+this.y]=this;	
}

Piece.prototype.setSize = function(w, h){
	if(ie){
		this.div.style.width=w;
		this.div.style.height=h;
	}else{
		this.div.style.width=w+'px';
		this.div.style.height=h+'px';
	}
}

Piece.prototype.checkDrop = function(c, e){
	var w, h;
		
	x=e.pageX-c.offx;
	y=e.pageY-c.offy;
	
	x=Math.floor(x/c.w);
	y=7-Math.floor(y/c.w);
	
	if(x==c.x && y==c.y){
		direct=false;
		c.setLocation(x, y);
		direct=true;
		return;
	}
	if(c.checkMove(x,y)){
		c.setLocation(x, y);
	}else{
		direct=false;
		c.setLocation(c.x, c.y);
		direct=true;
	}
}

Piece.prototype.checkMove = function(x, y){

	if(x<0 || x>7 || y<0 || y>7){
		return false;
	}else{
		if(this.board[x*8+y]==this.color){
			return false;
		}else{
			if(this.check(x, y)){
				this.board[this.x*8+this.y]=0;
				return true;						
			}else{
				return false
			}
		}
	}
	
}

Piece.prototype.checkPawnMove = function(x, y){
	var relx=Math.abs(x-this.x), rely=y-this.y;
	if(relx>1 || rely>2 || rely<0){
		return false;
	}
	if(relx!=0 && rely ==0){
		return false;
	}
	if(rely>2 && (this.board[x*8+y+1]>0)){
		return false;
	}
	if(relx==1 && rely==1 && !(this.board[x*8+y]>0 || this.board[x*8+y-1]>0)){
		return false;
	}
	if(relx==0 && this.board[x*8+y]>0){
		return false;
	}
	if(rely>1 && (relx>0 || this.y!=1) ){
		return false;
	}
	if(y==7){
		direct=false;
		showUpgrade(this);
	}
	return true;
}

Piece.prototype.checkRookMove = function(x, y){
	var relx=x-this.x, rely=y-this.y, i, j;
	if(relx!=0 && rely!=0){
		return false;
	}
	if(relx!=0){
		j=relx/Math.abs(relx);
		for(i=this.x+j; i!=x; i+=j){
			if(this.board[i*8+y]>0){
				return false;
			}
		}
	}
	if(rely!=0){
		j=rely/Math.abs(rely);
		for(i=this.y+j; i!=y; i+=j){
			if(this.board[this.x*8+i]>0){
				return false;
			}
		}
	}
	return true;
}

Piece.prototype.checkKnightMove = function(x, y){
	var relx=Math.abs(x-this.x), rely=Math.abs(y-this.y);
	if((relx==1 && rely == 2) || rely==1 && relx==2){
		return true;
	}
	return false;
}

Piece.prototype.checkBishopMove = function(x, y){
	var relx=x-this.x, rely=y-this.y, i, j, k, m;
	if(Math.abs(relx)!= Math.abs(rely)){
		return false;
	}
	k=relx/Math.abs(relx);
	m=rely/Math.abs(rely);
	j=this.y+m;
	for(i=this.x+k; i!=x; i+=k){
		if(this.board[i*8+j]>0){
			return false;
		}
		j+=m;
	}
	return true;
}

Piece.prototype.checkQueenMove = function(x, y){
	var relx=x-this.x, rely=y-this.y, i, j, k, m;
	if((Math.abs(relx)!= Math.abs(rely)) && rely!=0 && relx!=0){
		return false;
	}
	
	if(Math.abs(relx)>0){
		k=relx/Math.abs(relx);
	}else{
		k=0;
	}
	if(Math.abs(rely)>0){
		m=rely/Math.abs(rely);
	}else{
		m=0;
	}
	
	j=this.y+m;
	for(i=this.x+k; i!=x; i+=k){
		if(this.board[i*8+j]>0){
			return false;
		}
		j+=m;
	}
	return true;
}

Piece.prototype.checkKingMove = function(x, y){
	var relx=Math.abs(x-this.x), rely=Math.abs(y-this.y);
	if(rely>1 || relx>2){
		return false;
	}
	castling=false;
	if(relx>1){
		castling=true;
	}
	return true;
}

function signal_player_turn()
{
	if(turn == true)
	{
		$('#topdiv > .avatar > img').removeClass('player_turn');
		$('#bottomdiv > .avatar > img').addClass('player_turn');
	}
	else
	{
		$('#topdiv > .avatar > img').addClass('player_turn');
		$('#bottomdiv > .avatar > img').removeClass('player_turn');
	}
}

function PopupWindow(webpage)
{
	var url = webpage;
	var hWnd = window.open(url,"PHPChess","width=500,height=400,resizable=no,scrollbars=yes,status=yes");
	if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name="home"; hWnd.location.href=url; }}
} 