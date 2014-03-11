<?php

if(!defined('CHECK_PHPCHESS')){
	die("Hacking attempt");
	exit;
}

?>
<?php
//require_once("rsslib.php");
$white_colour = ""; $black_colour = "";
$oR3DCQuery->GetChessBoardColors('', $_SESSION['id'], &$white_colour, &$black_colour);
?>


<!-- saved from url=(0014)about:internet -->
	<script type="text/javascript">
		var white_tile_colour = "<?php echo $white_colour; ?>";
		var black_tile_colour = "<?php echo $black_colour; ?>";
	</script>
    <script type="text/javascript" src="javascriptchess/js/jquery-1.6.1.min.js"></script>
    <script type="text/javascript" src="javascriptchess/js/jquery-ui-1.7.2.custom.min.js"></script>
    <script type="text/javascript" src="javascriptchess/js/garbochess.js"></script>
	<script type="text/javascript" src="javascriptchess/js/boardui.js"></script>
	<script type="text/javascript">
	    $(document).ready(function () {
	        g_timeout = 1000;
	        UINewGame();
	    });
    </script>
    <div align="center">
        <div>
            Select Side:
			<select onchange="javascript:UIChangeStartPlayer()">
                <option value="white">White</option>
                <option value="black">Black</option>
            </select>
      		Bot time:
            <select id='TimerDropDown' onchange="javascript:UIChangeTimePerMove()">
                <option value="100">instant</option>
                <option value="1000">1 sec.</option>
             	<option value="2000">2 sec.</option>
                <option value="5000">5 sec.</option>
                <option value="10000">10 sec.</option>
                <option value="15000">15 sec.</option>
             	<option value="30000">30 sec.</option>
                <option value="60000">1 min.</option>
            </select>
            <input type="button" onclick="javascript:UINewGame()" value="New game">
        </div>
        <div>
        <br>
        FEN: <br>
        <input size="65"  id='FenTextBox' onchange="javascript:UIChangeFEN()"/>
        </div><br>
        <div>
		<select id='FenDropDown' onchange="javascript:UIChangeFEN2()">
        	<option value="rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNB1KBNR w KQkq -">normal starting position</option>
			<option value="r5rk/5p1p/5R2/4B3/8/8/7P/7K w">1 Mate in 3</option>
            <option value="5B2/6P1/1p6/8/1N6/kP6/2K5/8 w">Easy! or is it?</option>
            <option value="3n2nr/4Pqpp/2k5/8/8/8/2B3PP/6K1 w - - 0 1">Promote to what</option>
            <option value="8/R7/4kPP1/3ppp2/3B1P2/1K1P1P2/8/8 w - - 0 1">2 Mate in 3</option>
            <option value="r1bq2r1/b4pk1/p1pp1p2/1p2pP2/1P2P1PB/3P4/1PPQ2P1/R3K2R w -">mate in two</option>
            <option value="3r1r1k/1p3p1p/p2p4/4n1NN/6bQ/1BPq4/P3p1PP/1R5K w - - 0 1">3 Mate in 3.</option>
            <option value="rnb1kbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -">Dificulty Queen up</option>
            <option value="1nbqkbn1/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -">Dificulty 2 Rooks up</option>
            <option value="r1bqk1nr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -">Dificulty 1 Bish 1 Knight up</option>
            <option value="rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNB1KBNR w KQkq -">Dificulty Queen down</option>
            <option value="rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/1NBQKBN1 w KQkq -">Dificulty 2 Rooks down</option>
            <option value="rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RN1QKB1R w KQkq -">Dificulty 1 Bish 1 Knight down</option>
            <option value="rn1qk2r/1p2bppp/p2pbn2/4p3/4P3/1NN1BP2/PPP3PP/R2QKB1R w KQkq -">Opening balanced</option>
        </select>
        </div><br>
        <div style="margin-top:5px;">
			<div id='board'></div>
			<span id='output'></span><br/>
			PGN:<br/>
			<textarea cols='50' rows='6' id='PgnTextBox' readonly title="PGN"></textarea><br/>
			<div>
				<input type="button" onclick="javascript:UIAnalyzeToggle()" value="Analysis">
			</div>

        </div>
		<div style="margin-top: 30px; font-size: 10px">
			GarboChess Chess Engine<br/>Copyright (c) 2011 Gary Linscott
		</div>
     </div>
