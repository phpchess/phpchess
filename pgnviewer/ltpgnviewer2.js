// LT-PGN-VIEWER 2.63 by Lutz Tautenhahn (2001-2004)

var i, j, s, StartMove, MoveCount, MoveType, CanPass, EnPass, MaxMove=500, isInit=false, isCalculating=false;
var CurVar=0, activeAnchor=-1, startAnchor=-1, activeAnchorBG="#CCCCCC", TargetDocument, isSetupBoard=false, BoardSetupMode='copy';

ShortPgnMoveText=new Array(3);
for (i=0; i<3; i++) ShortPgnMoveText[i] = new Array();
ShortPgnMoveText[0][CurVar]="";

PieceType = new Array(2);
for (i=0; i<2; i++) PieceType[i] = new Array(16);
PiecePosX = new Array(2);
for (i=0; i<2; i++) PiecePosX[i] = new Array(16);
PiecePosY = new Array(2);
for (i=0; i<2; i++) PiecePosY[i] = new Array(16);
PieceMoves = new Array(2);
for (i=0; i<2; i++) PieceMoves[i] = new Array(16);

var isRotated=false;
var isRecording=false;
var RecordCount=0;
var AutoPlayInterval, isAutoPlay=false, Delay=1000;
var BoardClicked=-1;
var PieceName = "KQRBNP";
PieceCode = new Array(6);
for (i=0; i<6; i++) PieceCode[i]=PieceName.charCodeAt(i);

ColorName = new Array("w","b");
Castling = new Array(2);
for (i=0; i<2; i++) Castling[i] = new Array(2);
Board = new Array(8);
for (i=0; i<8; i++) Board[i] = new Array(8);

HalfMove = new Array(MaxMove+1);
HistMove = new Array(MaxMove);
HistPiece = new Array(2);
for (i=0; i<2; i++) HistPiece[i] = new Array(MaxMove);
HistType = new Array(2);
for (i=0; i<2; i++) HistType[i] = new Array(MaxMove);
HistPosX = new Array(2);
for (i=0; i<2; i++) HistPosX[i] = new Array(MaxMove);
HistPosY = new Array(2);
for (i=0; i<2; i++) HistPosY[i] = new Array(MaxMove);

BoardPic = new Array(2);
PiecePic = new Array(2);
for (i=0; i<2; i++)
  PiecePic[i] = new Array(6);
for (i=0; i<2; i++)
{ for (j=0; j<6; j++)
    PiecePic[i][j] = new Array(2);
}
LabelPic = new Array(5);

DocImg=new Array();

var ImagePathOld="-", ImagePath="./pgnviewer/", ImageOffset=0, IsLabelVisible=true, BottomLabels=64, RightLabels=65;

function SetImagePath(pp)
{ ImagePath=pp;
}

function SetImg(ii,ss)
{ if (DocImg[ii]==ss) return;
  DocImg[ii]=ss;
  document.images[ii+ImageOffset].src=ss;
}

function ShowLabels(bb)
{ IsLabelVisible=bb;
  RefreshBoard();
}

function SwitchLabels()
{ IsLabelVisible=!IsLabelVisible;
  RefreshBoard();
}

function InitImages()
{ if (ImagePathOld==ImagePath) return;
  var ii, jj;
  for (ii=0; ii<2; ii++)
  { BoardPic[ii] = new Image(); BoardPic[ii].src = ImagePath+ColorName[ii]+".gif";
  }
  for (ii=0; ii<2; ii++)
  { for (jj=0; jj<2; jj++)
    { PiecePic[ii][0][jj] = new Image(); PiecePic[ii][0][jj].src = ImagePath+ColorName[ii]+"k"+ColorName[jj]+".gif";
      PiecePic[ii][1][jj] = new Image(); PiecePic[ii][1][jj].src = ImagePath+ColorName[ii]+"q"+ColorName[jj]+".gif";
      PiecePic[ii][2][jj] = new Image(); PiecePic[ii][2][jj].src = ImagePath+ColorName[ii]+"r"+ColorName[jj]+".gif";
      PiecePic[ii][3][jj] = new Image(); PiecePic[ii][3][jj].src = ImagePath+ColorName[ii]+"b"+ColorName[jj]+".gif";
      PiecePic[ii][4][jj] = new Image(); PiecePic[ii][4][jj].src = ImagePath+ColorName[ii]+"n"+ColorName[jj]+".gif";
      PiecePic[ii][5][jj] = new Image(); PiecePic[ii][5][jj].src = ImagePath+ColorName[ii]+"p"+ColorName[jj]+".gif";
    }
  }
  LabelPic[0] = new Image(); LabelPic[0].src = ImagePath+"8_1.gif";
  LabelPic[1] = new Image(); LabelPic[1].src = ImagePath+"a_h.gif";
  LabelPic[2] = new Image(); LabelPic[2].src = ImagePath+"1_8.gif";
  LabelPic[3] = new Image(); LabelPic[3].src = ImagePath+"h_a.gif";
  LabelPic[4] = new Image(); LabelPic[4].src = ImagePath+"1x1.gif";
  ImagePathOld=ImagePath;
//ImageOffset=0;
  for (ii=0; ii<document.images.length; ii++)
  { if (document.images[ii]==document.images["BottomLabels"]) BottomLabels=ii;
    if (document.images[ii]==document.images["RightLabels"])
    { RightLabels=ii;
      if (ii>64) ImageOffset=ii-64;
    }  
  }
  DocImg.length=0;
}

function sign(nn)
{ if (nn>0) return(1);
  if (nn<0) return(-1);
  return(0);
}

function OpenUrl(ss)
{ if (ss!="")
    parent.frames[1].location.href = ss;
  else
  { if (document.BoardForm.Url.value!="")  
    { parent.frames[1].location.href = document.BoardForm.Url.value;
      if (document.BoardForm.OpenParsePgn.checked) setTimeout("ParsePgn(1)",400);
    }  
  }
}

function Init(rr)
{ var cc, ii, jj, kk, ll, nn, mm;
  isInit=true;
  if (isAutoPlay) SetAutoPlay(false);
  if (rr!='')
    FenString=rr;
  if (FenString=='standard')
    FenString="rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
  if ((document.BoardForm)&&(document.BoardForm.FEN))
      document.BoardForm.FEN.value=FenString;
  if (FenString == "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1")
  { for (ii=0; ii<2; ii++)
    { PieceType[ii][0]=0;
      PiecePosX[ii][0]=4;
      PieceType[ii][1]=1;
      PiecePosX[ii][1]=3;
      PieceType[ii][6]=2;
      PiecePosX[ii][6]=0;
      PieceType[ii][7]=2;
      PiecePosX[ii][7]=7;
      PieceType[ii][4]=3;
      PiecePosX[ii][4]=2;
      PieceType[ii][5]=3;
      PiecePosX[ii][5]=5;
      PieceType[ii][2]=4;
      PiecePosX[ii][2]=1;
      PieceType[ii][3]=4;
      PiecePosX[ii][3]=6;
      for (jj=0; jj<8; jj++)
      { PieceType[ii][jj+8]=5;
        PiecePosX[ii][jj+8]=jj;
      }
      for (jj=0; jj<16; jj++)
      { PieceMoves[ii][jj]=0;
        PiecePosY[ii][jj]=(1-ii)*Math.floor(jj/8)+ii*(7-Math.floor(jj/8));
      }
    }
    for (ii=0; ii<8; ii++)
    { for (jj=0; jj<8; jj++) Board[ii][jj]=0;
    }
    for (ii=0; ii<2; ii++)
    { for (jj=0; jj<16; jj++)
        Board[PiecePosX[ii][jj]][PiecePosY[ii][jj]]=(PieceType[ii][jj]+1)*(1-2*ii);
    }
    for (ii=0; ii<2; ii++)
    { for (jj=0; jj<2; jj++)
        Castling[ii][jj]=1;
    }
    EnPass=-1;
    HalfMove[0]=0;
    if (document.BoardForm)
    { RefreshBoard();
      if (document.BoardForm.Position)
        document.BoardForm.Position.value="";
    }
    StartMove=0;
    MoveCount=StartMove;
    MoveType=StartMove%2;
    BoardClicked=-1;
    CurVar=0;
    if (TargetDocument) HighlightMove("m"+MoveCount+"v"+CurVar);
  }
  else
  { for (ii=0; ii<2; ii++)
    { for (jj=0; jj<16; jj++)
      { PieceType[ii][jj]=-1;
        PiecePosX[ii][jj]=0;
        PiecePosY[ii][jj]=0;
        PieceMoves[ii][jj]=0;
      }
    }
    ii=0; jj=7; ll=0; nn=1; mm=1; cc=FenString.charAt(ll++);
    while (cc!=" ")
    { if (cc=="/")
      { if (ii!=8)
        { alert("Invalid FEN [1]: char "+ll+" in "+FenString);
          Init('standard');
          return;
        }
        ii=0;
        jj--;
      }
      if (ii==8) 
      { alert("Invalid FEN [2]: char "+ll+" in "+FenString);
        Init('standard');
        return;
      }
      if (! isNaN(cc))
      { ii+=parseInt(cc);
        if ((ii<0)||(ii>8))
        { alert("Invalid FEN [3]: char "+ll+" in "+FenString);
          Init('standard');
          return;
        }
      }
      if (cc.charCodeAt(0)==PieceName.toUpperCase().charCodeAt(0))
      { if (PieceType[0][0]!=-1)
        { alert("Invalid FEN [4]: char "+ll+" in "+FenString);
          Init('standard');
          return;
        }     
        PieceType[0][0]=0;
        PiecePosX[0][0]=ii;
        PiecePosY[0][0]=jj;
        ii++;
      }
      if (cc.charCodeAt(0)==PieceName.toLowerCase().charCodeAt(0))
      { if (PieceType[1][0]!=-1)
        { alert("Invalid FEN [5]: char "+ll+" in "+FenString);
          Init('standard');
          return;
        }  
        PieceType[1][0]=0;
        PiecePosX[1][0]=ii;
        PiecePosY[1][0]=jj;
        ii++;
      }
      for (kk=1; kk<6; kk++)
      { if (cc.charCodeAt(0)==PieceName.toUpperCase().charCodeAt(kk))
        { if (nn==16)
          { alert("Invalid FEN [6]: char "+ll+" in "+FenString);
            Init('standard');
            return;
          }          
          PieceType[0][nn]=kk;
          PiecePosX[0][nn]=ii;
          PiecePosY[0][nn]=jj;
          nn++;
          ii++;
        }
        if (cc.charCodeAt(0)==PieceName.toLowerCase().charCodeAt(kk))
        { if (mm==16)
          { alert("Invalid FEN [7]: char "+ll+" in "+FenString);
            Init('standard');
            return;
          }  
          PieceType[1][mm]=kk;
          PiecePosX[1][mm]=ii;
          PiecePosY[1][mm]=jj;
          mm++;
          ii++;
        }
      }
      if (ll<FenString.length)
        cc=FenString.charAt(ll++);
      else cc=" ";
    }
    if ((ii!=8)||(jj!=0))
    { alert("Invalid FEN [8]: char "+ll+" in "+FenString);
      Init('standard');
      return;
    }
    if ((PieceType[0][0]==-1)||(PieceType[1][0]==-1))
    { alert("Invalid FEN [9]: char "+ll+" missing king");
      Init('standard');
      return;
    }
    if (ll==FenString.length)
    { FenString+=" w ";
      FenString+=PieceName.toUpperCase().charAt(0);
      FenString+=PieceName.toUpperCase().charAt(1);
      FenString+=PieceName.toLowerCase().charAt(0);
      FenString+=PieceName.toLowerCase().charAt(1);      
      FenString+=" - 0 1";
      ll++;
    }
//    { alert("Invalid FEN [10]: char "+ll+" missing active color");
//      Init('standard');
//      return;
//    }
    cc=FenString.charAt(ll++);
    if ((cc=="w")||(cc=="b"))
    { if (cc=="w") StartMove=0;
      else StartMove=1;
    }
    else
    { alert("Invalid FEN [11]: char "+ll+" invalid active color");
      Init('standard');
      return;
    }
    ll++;
    if (ll>=FenString.length)
    { alert("Invalid FEN [12]: char "+ll+" missing castling availability");
      Init('standard');
      return;
    }
    Castling[0][0]=0; Castling[0][1]=0; Castling[1][0]=0; Castling[1][1]=0;
    cc=FenString.charAt(ll++);
    while (cc!=" ")
    { if (cc.charCodeAt(0)==PieceName.toUpperCase().charCodeAt(0))
        Castling[0][0]=1; 
      if (cc.charCodeAt(0)==PieceName.toUpperCase().charCodeAt(1))
        Castling[0][1]=1; 
      if (cc.charCodeAt(0)==PieceName.toLowerCase().charCodeAt(0))
        Castling[1][0]=1; 
      if (cc.charCodeAt(0)==PieceName.toLowerCase().charCodeAt(1))
        Castling[1][1]=1; 
      if (ll<FenString.length)
        cc=FenString.charAt(ll++);
      else cc=" ";
    }
    if (ll==FenString.length)
    { alert("Invalid FEN [13]: char "+ll+" missing en passant target square");
      Init('standard');
      return;
    }
    EnPass=-1;
    cc=FenString.charAt(ll++);
    while (cc!=" ")
    { if ((cc.charCodeAt(0)-97>=0)&&(cc.charCodeAt(0)-97<=7))
        EnPass=cc.charCodeAt(0)-97; 
      if (ll<FenString.length)
        cc=FenString.charAt(ll++);
      else cc=" ";
    }
    if (ll==FenString.length)
    { alert("Invalid FEN [14]: char "+ll+" missing halfmove clock");
      Init('standard');
      return;
    }
    HalfMove[0]=0;
    cc=FenString.charAt(ll++);
    while (cc!=" ")
    { if (isNaN(cc))
      { alert("Invalid FEN [15]: char "+ll+" invalid halfmove clock");
        Init('standard');
        return;
      }
      HalfMove[0]=HalfMove[0]*10+parseInt(cc);
      if (ll<FenString.length)
        cc=FenString.charAt(ll++);
      else cc=" ";
    }
    if (ll==FenString.length)
    { alert("Invalid FEN [16]: char "+ll+" missing fullmove number");
      Init('standard');
      return;
    }
    cc=FenString.substring(ll++);
    if (isNaN(cc))
    { alert("Invalid FEN [17]: char "+ll+" invalid fullmove number");
      Init('standard');
      return;
    }
    if (cc<=0)
    { alert("Invalid FEN [18]: char "+ll+" invalid fullmove number");
      Init('standard');
      return;
    }
    StartMove+=2*(parseInt(cc)-1);
    for (ii=0; ii<8; ii++)
    { for (jj=0; jj<8; jj++) Board[ii][jj]=0;
    }
    for (ii=0; ii<2; ii++)
    { for (jj=0; jj<16; jj++)
      { if (PieceType[ii][jj]!=-1) 
          Board[PiecePosX[ii][jj]][PiecePosY[ii][jj]]=(PieceType[ii][jj]+1)*(1-2*ii);
      }
    }
    if (document.BoardForm)
    { RefreshBoard();
      if (document.BoardForm.Position)
      { if (StartMove%2==0) document.BoardForm.Position.value="white to move";
        else document.BoardForm.Position.value="black to move";
      }  
    }
    MoveCount=StartMove;
    MoveType=StartMove%2;
    BoardClicked=-1;
    RecordCount=0;
    CurVar=0;
    if (TargetDocument) HighlightMove("m"+MoveCount+"v"+CurVar);
  }
}

function MoveBack(nn)
{ var ii, jj, cc;
  for (jj=0; (jj<nn)&&(MoveCount>StartMove); jj++)
  { if (RecordCount>0) RecordCount--;
    MoveCount--;
    MoveType=1-MoveType;
    cc=MoveCount-StartMove;
    ii=HistPiece[1][cc];
    if ((0<=ii)&&(ii<16)) //we must do this here because of Chess960 castling
    { Board[PiecePosX[MoveType][ii]][PiecePosY[MoveType][ii]]=0; 
      Board[HistPosX[1][cc]][HistPosY[1][cc]]=(HistType[1][cc]+1)*(1-2*MoveType);
    }
    ii=HistPiece[0][cc]; 
    Board[PiecePosX[MoveType][ii]][PiecePosY[MoveType][ii]]=0;
    Board[HistPosX[0][cc]][HistPosY[0][cc]]=(HistType[0][cc]+1)*(1-2*MoveType);
    PieceType[MoveType][ii]=HistType[0][cc];
    PiecePosX[MoveType][ii]=HistPosX[0][cc];
    PiecePosY[MoveType][ii]=HistPosY[0][cc];
    PieceMoves[MoveType][ii]--;
    ii=HistPiece[1][cc];
    if ((0<=ii)&&(ii<16))
    { PieceType[MoveType][ii]=HistType[1][cc];
      PiecePosX[MoveType][ii]=HistPosX[1][cc];
      PiecePosY[MoveType][ii]=HistPosY[1][cc];
      PieceMoves[MoveType][ii]--;
    }
    ii-=16;
    if (0<=ii)
    { Board[HistPosX[1][cc]][HistPosY[1][cc]]=(HistType[1][cc]+1)*(2*MoveType-1);
      PieceType[1-MoveType][ii]=HistType[1][cc];
      PiecePosX[1-MoveType][ii]=HistPosX[1][cc];
      PiecePosY[1-MoveType][ii]=HistPosY[1][cc];
      PieceMoves[1-MoveType][ii]--;
    }
    if (CurVar!=0)
    { if (MoveCount==ShortPgnMoveText[2][CurVar])
      { CurVar=ShortPgnMoveText[1][CurVar];
        if ((!isCalculating)&&(document.BoardForm)&&(document.BoardForm.PgnMoveText))
          document.BoardForm.PgnMoveText.value=ShortPgnMoveText[0][CurVar];
      }  
    }    
  }
  if (isCalculating) return;
  if (document.BoardForm)
  { RefreshBoard();
    if (document.BoardForm.Position)
    { if (MoveCount>StartMove)
        document.BoardForm.Position.value=HistMove[MoveCount-StartMove-1];
      else
        document.BoardForm.Position.value="";
    }    
  }
  if (TargetDocument) HighlightMove("m"+MoveCount+"v"+CurVar);
  if (AutoPlayInterval) clearTimeout(AutoPlayInterval);
  if (isAutoPlay) AutoPlayInterval=setTimeout("MoveBack("+nn+")", Delay);
}

function Uncomment(ss)
{ if (! ss) return(ss);
  var ii, jj, kk=ss.length, llist=ss.split("{"), ll=llist.length, uu=llist[0], tt;
  for (ii=1; ii<ll; ii++)
  { tt=llist[ii];
    jj=tt.indexOf("}")+1;
    if (jj>0) uu+=tt.substring(jj);
  }
  return(uu);
}

function MoveForward(nn, rr)
{ var ii,ffst=0,llst,ssearch,ssub,ffull,mmove0="",mmove1="";
  if (rr);
  else
  { if ((document.BoardForm)&&(document.BoardForm.PgnMoveText))
      ShortPgnMoveText[0][CurVar]=document.BoardForm.PgnMoveText.value;
  }
  ffull=Uncomment(ShortPgnMoveText[0][CurVar]);
  for (ii=0; (ii<nn)&&(ffst>=0)&&(MoveCount<MaxMove); ii++)
  { ssearch=Math.floor(MoveCount/2+2)+".";
    llst=ffull.indexOf(ssearch);
    ssearch=Math.floor(MoveCount/2+1)+".";
    ffst=ffull.indexOf(ssearch);
    if (ffst>=0)
    { ffst+=ssearch.length;
      if (llst<0)
        ssub=ffull.substring(ffst);
      else
        ssub=ffull.substring(ffst, llst);
      mmove0=GetMove(ssub,MoveType);
      if (mmove0!="")
      { if (ParseMove(mmove0, true)>0)
        { mmove1=mmove0;
          if (MoveType==0)
            HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+"."+mmove1;
          else
            HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+". ... "+mmove1;
          MoveCount++;
          MoveType=1-MoveType;
        }  
        else
        { if (MoveType==1)
          { ssub=Math.floor(MoveCount/2+1);
            ssearch=ssub+"....";
            ffst=ffull.indexOf(ssearch);
            if (ffst<0)
            { ssearch=ssub+"...";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+". ...";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+". ..";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+" ...";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+" ..";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst>=0) 
            { ffst+=ssearch.length;
              if (llst<0)
                ssub=ffull.substring(ffst);
              else
                ssub=ffull.substring(ffst, llst);
              mmove0=GetMove(ssub,0);
              if (mmove0!="")
              { if (ParseMove(mmove0, true)>0)
                { mmove1=mmove0;
                  HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+". ... "+mmove1;
                  MoveCount++;
                  MoveType=1-MoveType;
                }  
                else
                { ffst=-1;
                  //alert(mmove0+" is not a valid move.");
                }
              }
            }
          }
          else
          { ffst=-1;
            //alert(mmove0+" is not a valid move.");
          }
        }
      }
      else ffst=-1;
    }
  }
  if (isCalculating) return;
  if (document.BoardForm)
  { if ((document.BoardForm.Position)&&(mmove1!=""))
      document.BoardForm.Position.value=HistMove[MoveCount-StartMove-1];
    RefreshBoard();
  }
  if (TargetDocument) HighlightMove("m"+MoveCount+"v"+CurVar);
  if (AutoPlayInterval) clearTimeout(AutoPlayInterval);
  if (isAutoPlay) AutoPlayInterval=setTimeout("MoveForward("+nn+")", Delay);
}

function ParseMove(mm, sstore)
{ var ii, ffrom="", ccapt=0, ll, yy1i=-1;
  var ttype0=-1, xx0=-1, yy0=-1, ttype1=-1, xx1=-1, yy1=-1;
  if (MoveCount>StartMove)
  { CanPass=-1;
    ii=HistPiece[0][MoveCount-StartMove-1];
    if ((HistType[0][MoveCount-StartMove-1]==5)&&(Math.abs(HistPosY[0][MoveCount-StartMove-1]-PiecePosY[1-MoveType][ii])==2))
      CanPass=PiecePosX[1-MoveType][ii];
  }
  else
    CanPass=EnPass;
  ii=1;
  while (ii<mm.length)  
  { if (! isNaN(mm.charAt(ii)))
    { xx1=mm.charCodeAt(ii-1)-97;
      yy1=mm.charAt(ii)-1;
      yy1i=ii;
      ffrom=mm.substring(0, ii-1);
    }
    ii++;
  }
  if ((xx1<0)||(xx1>7)||(yy1<0)||(yy1>7))
  { if ((mm.indexOf("O")>=0)||(mm.indexOf("0")>=0))
    { if ((mm.indexOf("O-O-O")>=0)||(mm.indexOf("0-0-0")>=0)||(mm.indexOf("O朞朞")>=0)||(mm.indexOf("0𢠢")>=0)) 
      { if (EvalMove(ttype0, 6, xx0, yy0, ttype1, xx1, yy1, ccapt, sstore))
          return(1);
        return(0);
      }
      if ((mm.indexOf("O-O")>=0)||(mm.indexOf("0-0")>=0)||(mm.indexOf("O朞")>=0)||(mm.indexOf("0�0")>=0))
      { if (EvalMove(ttype0, 7, xx0, yy0, ttype1, xx1, yy1, ccapt, sstore))
          return(1);
        return(0);
      }
      return(0);
    }
    if (mm.indexOf("...")>=0) 
    { if (EvalMove(ttype0, 8, xx0, yy0, ttype1, xx1, yy1, ccapt, sstore))
        return(1);
      return(0);
    }
    return(0);
  }
  ll=ffrom.length;
  ttype0=5;
  if (ll>0)
  { for (ii=0; ii<5; ii++)
    { if (ffrom.charCodeAt(0)==PieceCode[ii]) 
        ttype0=ii;
    }
    if (ffrom.charAt(ll-1)=="x") ccapt=1;
    else
    { if ((ffrom.charAt(ll-1)=="-")||(ffrom.charAt(ll-1)=="�")) ll--; //Smith Notation
    }
    if (isNaN(mm.charAt(ll-1-ccapt)))
    { xx0=ffrom.charCodeAt(ll-1-ccapt)-97;
      if ((xx0<0)||(xx0>7)) xx0=-1;
    }
    else
    { yy0=ffrom.charAt(ll-1-ccapt)-1;
      if ((yy0<0)||(yy0>7)) yy0=-1;
    }
    if ((yy0>=0)&&(isNaN(mm.charAt(ll-2-ccapt)))) //Smith Notation
    { xx0=ffrom.charCodeAt(ll-2-ccapt)-97;
      if ((xx0<0)||(xx0>7)) xx0=-1;
      else
      { ttype0=Math.abs(Board[xx0][yy0])-1;
        if ((ttype0==0)&&(xx0-xx1>1))
        { if (EvalMove(ttype0, 6, xx0, yy0, -1, -1, -1, 0, sstore))
            return(1);
          return(0);
        }  
        if ((ttype0==0)&&(xx1-xx0>1))
        { if (EvalMove(ttype0, 7, xx0, yy0, -1, -1, -1, 0, sstore))
            return(1);
          return(0);
        }
      }
    }
  }
  if (Board[xx1][yy1]!=0) ccapt=1;
  else
  { if ((ttype0==5)&&(xx1==CanPass)&&(yy1==5-3*MoveType)) ccapt=1;
  }
  ttype1=ttype0;
  ii=mm.indexOf("=");
  if (ii<0) ii=yy1i;
  if ((ii>0)&&(ii<mm.length-1))
  { if (ttype0==5)
    { ii=mm.charCodeAt(ii+1);
      if (ii==PieceCode[1]) ttype1=1;
      if (ii==PieceCode[2]) ttype1=2;
      if (ii==PieceCode[3]) ttype1=3;
      if (ii==PieceCode[4]) ttype1=4;
    }  
  }
  if (sstore)
  { for (ii=0; ii<16; ii++)
    { if (PieceType[MoveType][ii]==ttype0)
      { if (EvalMove(ii, ttype0, xx0, yy0, ttype1, xx1, yy1, ccapt, true))
          return(1);
      }
    }
  }
  else
  { ll=0
    for (ii=0; ii<16; ii++)
    { if (PieceType[MoveType][ii]==ttype0)
      { if (EvalMove(ii, ttype0, xx0, yy0, ttype1, xx1, yy1, ccapt, false))
          ll++;
      }
    }
    return(ll);
  }    
  return(0);
}

function EvalMove(ii, ttype0, xx0, yy0, ttype1, xx1, yy1, ccapt, sstore)
{ var ddx, ddy, xx, yy, jj=-1, ttype2=-1, xx2=xx1, yy2=xx1, ttype3=-1, xx3=-1, yy3=-1, ff;
  if (ttype0==6) //O-O-O with Chess960 rules
  { if (Castling[MoveType][1]==0) return(false);
    if (PieceMoves[MoveType][0]>0) return(false);
    jj=0;
    while (jj<16)
    { if ((PiecePosX[MoveType][jj]<PiecePosX[MoveType][0])&&
          (PiecePosY[MoveType][jj]==MoveType*7)&&
          (PieceType[MoveType][jj]==2))
        jj+=100;
      else jj++;
    }
    if (jj==16) return(false);
    jj-=100;
    if (PieceMoves[MoveType][jj]>0) return(false);
    Board[PiecePosX[MoveType][0]][MoveType*7]=0;
    Board[PiecePosX[MoveType][jj]][MoveType*7]=0;
    ff=PiecePosX[MoveType][jj];
    if (ff>2) ff=2;
    while ((ff<PiecePosX[MoveType][0])||(ff<=3))
    { if (Board[ff][MoveType*7]!=0)
      { Board[PiecePosX[MoveType][0]][MoveType*7]=1-2*MoveType;
        Board[PiecePosX[MoveType][jj]][MoveType*7]=(1-2*MoveType)*3;
        return(false);
      }
      ff++;
    }
    Board[PiecePosX[MoveType][0]][MoveType*7]=1-2*MoveType;
    Board[PiecePosX[MoveType][jj]][MoveType*7]=(1-2*MoveType)*3;  
    if (StoreMove(0, 0, 2, MoveType*7, jj, 2, 3, MoveType*7, sstore))
      return(true);
    return(false);
  }
  if (ttype0==7) //O-O with Chess960 rules
  { if (Castling[MoveType][0]==0) return(false);
    if (PieceMoves[MoveType][0]>0) return(false);
    jj=0;
    while (jj<16)
    { if ((PiecePosX[MoveType][jj]>PiecePosX[MoveType][0])&&
          (PiecePosY[MoveType][jj]==MoveType*7)&&
          (PieceType[MoveType][jj]==2))
        jj+=100;
      else jj++;
    }
    if (jj==16) return(false);
    jj-=100;
    if (PieceMoves[MoveType][jj]>0) return(false);
    Board[PiecePosX[MoveType][0]][MoveType*7]=0;
    Board[PiecePosX[MoveType][jj]][MoveType*7]=0;
    ff=PiecePosX[MoveType][jj];
    if (ff<6) ff=6;
    while ((ff>PiecePosX[MoveType][0])||(ff>=5))
    { if (Board[ff][MoveType*7]!=0)
      { Board[PiecePosX[MoveType][0]][MoveType*7]=1-2*MoveType;
        Board[PiecePosX[MoveType][jj]][MoveType*7]=(1-2*MoveType)*3;
        return(false);
      }
      ff--;
    }
    Board[PiecePosX[MoveType][0]][MoveType*7]=1-2*MoveType;
    Board[PiecePosX[MoveType][jj]][MoveType*7]=(1-2*MoveType)*3;      
    if (StoreMove(0, 0, 6, MoveType*7, jj, 2, 5, MoveType*7, sstore))
      return(true);
    return(false);
  }
  if (ttype0==8) // ... NullMove
  { if (StoreMove(0, 0, PiecePosX[MoveType][0], PiecePosY[MoveType][0], -1, -1, -1, -1, sstore))
      return(true);
    return(false);
  }  
  if ((PiecePosX[MoveType][ii]==xx1)&&(PiecePosY[MoveType][ii]==yy1))
    return(false);
  if ((ccapt==0)&&(Board[xx1][yy1]!=0))
    return(false);
  if ((ccapt>0)&&(sign(Board[xx1][yy1])!=(2*MoveType-1)))
  { if ((ttype0!=5)||(CanPass!=xx1)||(yy1!=5-3*MoveType))
      return(false);
  }
  if ((xx0>=0)&&(xx0!=PiecePosX[MoveType][ii])) return(false);
  if ((yy0>=0)&&(yy0!=PiecePosY[MoveType][ii])) return(false);
  if (ttype0==0)
  { //if ((xx0>=0)||(yy0>=0)) return(false); //because of Smith Notation
    if (Math.abs(PiecePosX[MoveType][ii]-xx1)>1) return(false);
    if (Math.abs(PiecePosY[MoveType][ii]-yy1)>1) return(false);
  }
  if (ttype0==1)
  { if ((Math.abs(PiecePosX[MoveType][ii]-xx1)!=Math.abs(PiecePosY[MoveType][ii]-yy1))&&
        ((PiecePosX[MoveType][ii]-xx1)*(PiecePosY[MoveCount%2][ii]-yy1)!=0))
      return(false);
  }
  if (ttype0==2)
  { if ((PiecePosX[MoveType][ii]-xx1)*(PiecePosY[MoveType][ii]-yy1)!=0)
      return(false);
  }
  if (ttype0==3)
  { if (Math.abs(PiecePosX[MoveType][ii]-xx1)!=Math.abs(PiecePosY[MoveType][ii]-yy1))
      return(false);
  }
  if (ttype0==4)
  { if (Math.abs(PiecePosX[MoveType][ii]-xx1)*Math.abs(PiecePosY[MoveType][ii]-yy1)!=2)
      return(false);
  }
  if ((ttype0==1)||(ttype0==2)||(ttype0==3))
  { ddx=sign(xx1-PiecePosX[MoveType][ii]);
    ddy=sign(yy1-PiecePosY[MoveType][ii]);
    xx=PiecePosX[MoveType][ii]+ddx;
    yy=PiecePosY[MoveType][ii]+ddy;
    while ((xx!=xx1)||(yy!=yy1))
    { if (Board[xx][yy]!=0) return(false);
      xx+=ddx;
      yy+=ddy;
    }
  }
  if (ttype0==5)
  { if (Math.abs(PiecePosX[MoveType][ii]-xx1)!=ccapt) return(false);
    if ((yy1==7*(1-MoveType))&&(ttype0==ttype1)) return(false);
    if (ccapt==0)
    { if (PiecePosY[MoveType][ii]-yy1==4*MoveType-2)
      { if (PiecePosY[MoveType][ii]!=1+5*MoveType) return(false);
        if (Board[xx1][yy1+2*MoveType-1]!=0) return(false);
      }
      else
      { if (PiecePosY[MoveType][ii]-yy1!=2*MoveType-1) return(false);
      }
    }
    else
    { if (PiecePosY[MoveType][ii]-yy1!=2*MoveType-1) return(false);
    }
  }
  if (ttype1!=ttype0)
  { if (ttype0!=5) return(false);
    if (ttype1>=5) return(false);
    if (yy1!=7-7*MoveType) return(false);
  }
  if ((ttype0<=5)&&(ccapt>0))
  { jj=15;
    while ((jj>=0)&&(ttype3<0))
    { if ((PieceType[1-MoveType][jj]>0)&&
          (PiecePosX[1-MoveType][jj]==xx1)&&
          (PiecePosY[1-MoveType][jj]==yy1))
        ttype3=PieceType[1-MoveType][jj];
      else
        jj--;
    }
    if ((ttype3==-1)&&(ttype0==5)&&(CanPass>=0))
    { jj=15;
      while ((jj>=0)&&(ttype3<0))
      { if ((PieceType[1-MoveType][jj]==5)&&
            (PiecePosX[1-MoveType][jj]==xx1)&&
            (PiecePosY[1-MoveType][jj]==yy1-1+2*MoveType))
          ttype3=PieceType[1-MoveType][jj];
        else
          jj--;
      }
    }
    ttype3=-1;
  }  
  if (StoreMove(ii, ttype1, xx1, yy1, jj, ttype3, xx3, yy3, sstore))
    return(true);
  return(false);
}

function StoreMove(ii, ttype1, xx1, yy1, jj, ttype3, xx3, yy3, sstore)
{ var iis_check=0, ll, cc=MoveCount-StartMove, ff=PiecePosX[MoveType][0];
  if ((ttype1==5)||(ttype3>=0))
    HalfMove[cc+1]=0;
  else
    HalfMove[cc+1]=HalfMove[cc]+1;
  HistPiece[0][cc] = ii;
  HistType[0][cc] = PieceType[MoveType][ii];
  HistPosX[0][cc] = PiecePosX[MoveType][ii];
  HistPosY[0][cc] = PiecePosY[MoveType][ii];
  if (jj<0) 
    HistPiece[1][cc] = -1;
  else
  { if (ttype3>=0)
    { HistPiece[1][cc] = jj;
      HistType[1][cc] = PieceType[MoveType][jj];
      HistPosX[1][cc] = PiecePosX[MoveType][jj];
      HistPosY[1][cc] = PiecePosY[MoveType][jj];
    }
    else
    { HistPiece[1][cc] = 16+jj;
      HistType[1][cc] = PieceType[1-MoveType][jj];
      HistPosX[1][cc] = PiecePosX[1-MoveType][jj];
      HistPosY[1][cc] = PiecePosY[1-MoveType][jj];
    }
  }

  Board[PiecePosX[MoveType][ii]][PiecePosY[MoveType][ii]]=0;
  if (jj>=0)
  { if (ttype3<0)
      Board[PiecePosX[1-MoveType][jj]][PiecePosY[1-MoveType][jj]]=0;
    else
      Board[PiecePosX[MoveType][jj]][PiecePosY[MoveType][jj]]=0;
  }
  PieceType[MoveType][ii]=ttype1;
  PiecePosX[MoveType][ii]=xx1;
  PiecePosY[MoveType][ii]=yy1;
  PieceMoves[MoveType][ii]++;
  if (jj>=0)
  { if (ttype3<0)
    { PieceType[1-MoveType][jj]=ttype3;
      PieceMoves[1-MoveType][jj]++;
    }
    else
    { PiecePosX[MoveType][jj]=xx3;
      PiecePosY[MoveType][jj]=yy3;
      PieceMoves[MoveType][jj]++;
    }
  }
  if (jj>=0)
  { if (ttype3<0)
      Board[PiecePosX[1-MoveType][jj]][PiecePosY[1-MoveType][jj]]=0;    
    else
      Board[PiecePosX[MoveType][jj]][PiecePosY[MoveType][jj]]=(PieceType[MoveType][jj]+1)*(1-2*MoveType);
  }
  Board[PiecePosX[MoveType][ii]][PiecePosY[MoveType][ii]]=(PieceType[MoveType][ii]+1)*(1-2*MoveType);

  if ((ttype1==0)&&(ttype3==2)) //O-O-O, O-O
  { while (ff>xx1) 
    { iis_check+=IsCheck(ff, MoveType*7, MoveType);
      ff--;      
    }
    while (ff<xx1) 
    { iis_check+=IsCheck(ff, MoveType*7, MoveType);
      ff++;      
    } 
  }
  iis_check+=IsCheck(PiecePosX[MoveType][0], PiecePosY[MoveType][0], MoveType);

  if ((iis_check==0)&&(sstore)) return(true);

  Board[PiecePosX[MoveType][ii]][PiecePosY[MoveType][ii]]=0;
  Board[HistPosX[0][cc]][HistPosY[0][cc]]=(HistType[0][cc]+1)*(1-2*MoveType);
  PieceType[MoveType][ii]=HistType[0][cc];
  PiecePosX[MoveType][ii]=HistPosX[0][cc];
  PiecePosY[MoveType][ii]=HistPosY[0][cc];
  PieceMoves[MoveType][ii]--;
  if (jj>=0)   
  { if (ttype3>=0)
    { Board[PiecePosX[MoveType][jj]][PiecePosY[MoveType][jj]]=0;
      Board[HistPosX[1][cc]][HistPosY[1][cc]]=(HistType[1][cc]+1)*(1-2*MoveType);
      PieceType[MoveType][jj]=HistType[1][cc];
      PiecePosX[MoveType][jj]=HistPosX[1][cc];
      PiecePosY[MoveType][jj]=HistPosY[1][cc];
      PieceMoves[MoveType][jj]--;
    }
    else
    { Board[HistPosX[1][cc]][HistPosY[1][cc]]=(HistType[1][cc]+1)*(2*MoveType-1);
      PieceType[1-MoveType][jj]=HistType[1][cc];
      PiecePosX[1-MoveType][jj]=HistPosX[1][cc];
      PiecePosY[1-MoveType][jj]=HistPosY[1][cc];
      PieceMoves[1-MoveType][jj]--;
    }
  }
  if (iis_check==0) return(true);
  return(false); 
}

function IsCheck(xx, yy, tt)
{ var ii0=xx, jj0=yy, ddi, ddj, bb;
  for (ddi=-2; ddi<=2; ddi+=4)
  { for (ddj=-1; ddj<=1; ddj+=2)
    { if (IsOnBoard(ii0+ddi, jj0+ddj))  
      { if (Board[ii0+ddi][jj0+ddj]==10*tt-5) return(1);
      }
    }
  }
  for (ddi=-1; ddi<=1; ddi+=2)
  { for (ddj=-2; ddj<=2; ddj+=4)
    { if (IsOnBoard(ii0+ddi, jj0+ddj)) 
      { if (Board[ii0+ddi][jj0+ddj]==10*tt-5) return(1);
      }
    }
  }
  for (ddi=-1; ddi<=1; ddi+=2)
  { ddj=1-2*tt;
    { if (IsOnBoard(ii0+ddi, jj0+ddj)) 
      { if (Board[ii0+ddi][jj0+ddj]==12*tt-6) return(1);
      }
    }
  }
  if ((Math.abs(PiecePosX[1-tt][0]-xx)<2)&&(Math.abs(PiecePosY[1-tt][0]-yy)<2)) 
    return(1);
  for (ddi=-1; ddi<=1; ddi+=1)
  { for (ddj=-1; ddj<=1; ddj+=1)
    { if ((ddi!=0)||(ddj!=0))
      { ii0=xx+ddi; 
        jj0=yy+ddj;
        bb=0;
        while ((IsOnBoard(ii0, jj0))&&(bb==0))
        { bb=Board[ii0][jj0];
          if (bb==0)
          { ii0+=ddi;
            jj0+=ddj;
          }
          else
          { if (bb==4*tt-2) return(1); 
            if ((bb==6*tt-3)&&((ddi==0)||(ddj==0))) return(1); 
            if ((bb==4*tt-4)&&(ddi!=0)&&(ddj!=0)) return(1); 
          }  
        }
      }
    }
  }
  return(0);
}

function IsOnBoard(ii, jj)
{ if (ii<0) return(false);
  if (ii>7) return(false);
  if (jj<0) return(false);
  if (jj>7) return(false);
  return(true);
}

function GetMove(tt,nn)
{ var ii=0, jj=0, mm="", ll=-1, cc, ss=tt;
  while (ss.indexOf("<br />")>0) ss=ss.replace("<br />","");
  var kk=ss.length;
  while (ii<kk)
  { cc=ss.charCodeAt(ii);
    if ((cc<=32))//||(cc==46)) //||(cc>=127))
    { if (ll+1!=ii) jj++;
      ll=ii;
    }
    else
    { if (jj==nn) mm+=ss.charAt(ii);
    }
    ii++;
  }
  if ((nn==1)&&(mm=="")&&(ss.charAt(0)=="."))
  { ii=0;
    while (ii<kk)
    { cc=ss.charAt(ii);
      if ((cc!=".")||(mm!="")) mm+=cc;
      ii++;
    }  
  }
  return(mm);
}

function RefreshBoard(rr)
{ InitImages();
  if (rr) DocImg.length=0;
  var ii, jj;
  if (document.images["RightLabels"])
  { if (IsLabelVisible)
    { if (isRotated) SetImg(RightLabels,LabelPic[2].src);
      else SetImg(RightLabels,LabelPic[0].src);
    }
    else SetImg(RightLabels,LabelPic[4].src);
  }
  if (document.images["BottomLabels"])
  { if (IsLabelVisible)
    { if (isRotated) SetImg(BottomLabels,LabelPic[3].src);
      else SetImg(BottomLabels,LabelPic[1].src);
    }
    else SetImg(BottomLabels,LabelPic[4].src); 
  }  
  for (ii=0; ii<8; ii++)
  { for (jj=0; jj<8; jj++)
    { if (Board[ii][jj]==0)
      { if (isRotated)
          SetImg(63-ii-(7-jj)*8,BoardPic[(ii+jj+1)%2].src);
        else
          SetImg(ii+(7-jj)*8,BoardPic[(ii+jj+1)%2].src);
      }
    }
  }
  for (ii=0; ii<2; ii++)
  { for (jj=0; jj<16; jj++)
    { if (PieceType[ii][jj]>=0)
      { kk=PiecePosX[ii][jj]+8*(7-PiecePosY[ii][jj]);
        if (isRotated)
          SetImg(63-kk,PiecePic[ii][PieceType[ii][jj]][(kk+Math.floor(kk/8))%2].src);  
        else
          SetImg(kk,PiecePic[ii][PieceType[ii][jj]][(kk+Math.floor(kk/8))%2].src);
      }
    }
  }
  return;
  alert("Showing Board:");
  for (ii=0; ii<8; ii++)
  { for (jj=0; jj<8; jj++)
    { if (Board[ii][jj]==0)
        SetImg(ii+(7-jj)*8,BoardPic[(ii+jj+1)%2].src);
      else
        SetImg(ii+(7-jj)*8,PiecePic[(1-sign(Board[ii][jj]))/2][Math.abs(Board[ii][jj])-1][(ii+jj+1)%2].src);
    }
  }
}

function BoardClick(nn)
{ var ii0, jj0, ii1, jj1, mm, nnn;
  var pp, ffst=0, ssearch, ssub;
  if (isSetupBoard) { SetupBoardClick(nn); return; }
  if (! isRecording) return;
  if (MoveCount==MaxMove) return;
  if (isRotated) nnn=63-nn;
  else nnn=nn;
  if (BoardClicked==nnn) { BoardClicked=-1; return; }
  if (BoardClicked<0) 
  { ii0=nnn%8;
    jj0=7-(nnn-ii0)/8;
    if (sign(Board[ii0][jj0])==0) return;
    if (sign(Board[ii0][jj0])!=((MoveCount+1)%2)*2-1) 
    { mm="...";
      if ((document.BoardForm)&&(document.BoardForm.PgnMoveText))
        ShortPgnMoveText[0][CurVar]=Uncomment(document.BoardForm.PgnMoveText.value);
      ssearch=Math.floor(MoveCount/2+1)+".";
      ffst=ShortPgnMoveText[0][CurVar].indexOf(ssearch);
      if (ffst>=0)
        ssub=ShortPgnMoveText[0][CurVar].substring(0, ffst);
      else
        ssub=ShortPgnMoveText[0][CurVar]; 
      if (ParseMove(mm, false)==0) { BoardClicked=-1; return; }
      if (MoveCount%2==0) { if (!confirm("White nullmove?")) return; }
      else { if (!confirm("Black nullmove?")) return; }
      ParseMove(mm,true);
      if (MoveType==0)
      { HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+"."+mm;
        ssub+=Math.floor((MoveCount+2)/2)+".";
      }  
      else
      { HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+". ... "+mm;
        if (MoveCount==StartMove) ssub+=Math.floor((MoveCount+2)/2)+". ... ";
        else ssub+=HistMove[MoveCount-StartMove-1]+" ";
      }
      RecordCount++;
      MoveCount++;
      MoveType=1-MoveType;
      if (document.BoardForm)
      { if (document.BoardForm.PgnMoveText) document.BoardForm.PgnMoveText.value=ssub+mm+" ";
        if (document.BoardForm.Position)
          document.BoardForm.Position.value=HistMove[MoveCount-StartMove-1];
        RefreshBoard();  	
      }
    }
    BoardClicked=nnn; 
    return; 
  } 
  ii0=BoardClicked%8;
  jj0=7-(BoardClicked-ii0)/8;
  ii1=nnn%8;
  jj1=7-(nnn-ii1)/8;
  if (Math.abs(Board[ii0][jj0])==6)
  { if (ii0!=ii1) mm=String.fromCharCode(ii0+97)+"x";
    else mm="";
  }
  else
  { mm=PieceName.charAt(Math.abs(Board[ii0][jj0])-1);
    if (Board[ii1][jj1]!=0) mm+="x";
  }
  BoardClicked=-1;
  mm+=String.fromCharCode(ii1+97)+eval(jj1+1);
  if (Math.abs(Board[ii0][jj0])==1)
  { if (PiecePosY[MoveType][0]==jj1)
    { if (PiecePosX[MoveType][0]+2==ii1) mm="O-O";
      if (PiecePosX[MoveType][0]-2==ii1) mm="O-O-O";
      if (Board[ii1][jj1]==(1-2*MoveType)*3) //for Chess960
      { if (ii1>ii0) mm="O-O";
        if (ii1<ii0) mm="O-O-O";
      }
    }  
  }     
  if ((document.BoardForm)&&(document.BoardForm.PgnMoveText))
    ShortPgnMoveText[0][CurVar]=Uncomment(document.BoardForm.PgnMoveText.value);
  ssearch=Math.floor(MoveCount/2+1)+".";
  ffst=ShortPgnMoveText[0][CurVar].indexOf(ssearch);
  if (ffst>=0)
    ssub=ShortPgnMoveText[0][CurVar].substring(0, ffst);
  else
    ssub=ShortPgnMoveText[0][CurVar]; 
  if ((jj1==(1-MoveType)*7)&&(Math.abs(Board[ii0][jj0])==6)&&(Math.abs(jj0-jj1)<=1)&&(Math.abs(ii0-ii1)<=1))
  { pp=0;
    while(pp==0)
    { if (pp==0) { if (confirm("Queen "+PieceName.charAt(1)+" ?")) pp=1; }
      if (pp==0) { if (confirm("Rock "+PieceName.charAt(2)+" ?")) pp=2; }
      if (pp==0) { if (confirm("Bishop "+PieceName.charAt(3)+" ?")) pp=3; }
      if (pp==0) { if (confirm("Knight "+PieceName.charAt(4)+" ?")) pp=4; }            
    }
    mm=mm+"="+PieceName.charAt(pp);
  }
  pp=ParseMove(mm, false);
  if (pp==0) return;
  if (pp>1)
  { mm=mm.substr(0,1)+String.fromCharCode(ii0+97)+mm.substr(1,11);
    if (ParseMove(mm, false)==1)
      ParseMove(mm, true);
    else
    { mm=mm.substr(0,1)+eval(jj0+1)+mm.substr(2,11);
      if (ParseMove(mm, false)==1)
        ParseMove(mm, true);
      else
      { mm=mm.substr(0,1)+String.fromCharCode(ii0+97)+eval(jj0+1)+mm.substr(2,11);
      	ParseMove(mm, true);
      }  
    }  
  }
  else ParseMove(mm,true);
  if (IsCheck(PiecePosX[1-MoveType][0], PiecePosY[1-MoveType][0], 1-MoveType)) mm+="+";
  if (MoveType==0)
  { HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+"."+mm;
    ssub+=Math.floor((MoveCount+2)/2)+".";
  }  
  else
  { HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+". ... "+mm;
    if (MoveCount==StartMove) ssub+=Math.floor((MoveCount+2)/2)+". ... ";
    else ssub+=HistMove[MoveCount-StartMove-1]+" ";
  }
  RecordCount++;
  MoveCount++;
  MoveType=1-MoveType;
  if (document.BoardForm)
  { if (document.BoardForm.PgnMoveText) document.BoardForm.PgnMoveText.value=ssub+mm+" ";
    if (document.BoardForm.Position)
      document.BoardForm.Position.value=HistMove[MoveCount-StartMove-1];
    RefreshBoard();  	
  }
}

function SwitchAutoPlay()
{ if (isAutoPlay) SetAutoPlay(false);
  else SetAutoPlay(true);
}
function SetAutoPlay(bb)
{ isAutoPlay=bb;
  if (AutoPlayInterval) clearTimeout(AutoPlayInterval);
  if (isAutoPlay)
  { if ((document.BoardForm)&&(document.BoardForm.AutoPlay))
      document.BoardForm.AutoPlay.value="stop";
    MoveForward(1);
  }
  else
  { if ((document.BoardForm)&&(document.BoardForm.AutoPlay))
      document.BoardForm.AutoPlay.value="play";
  }
}
function SetDelay(vv)
{ Delay=vv;
}
function RotateBoard(bb)
{ isRotated=bb;
  if ((document.BoardForm)&&(document.BoardForm.Rotated))
    document.BoardForm.Rotated.checked=bb;
  RefreshBoard();
}
function AllowRecording(bb)
{ if ((document.BoardForm)&&(document.BoardForm.Recording))
    document.BoardForm.Recording.checked=bb;
  isRecording=bb;
}
function SetPgnMoveText(ss, vvariant, rroot, sstart)
{ if ((document.BoardForm)&&(document.BoardForm.PgnMoveText))
    document.BoardForm.PgnMoveText.value=ss;
  if (vvariant)
  { ShortPgnMoveText[0][vvariant]=ss;
    ShortPgnMoveText[1][vvariant]=rroot;
    ShortPgnMoveText[2][vvariant]=sstart;
  }
  else ShortPgnMoveText[0][0]=ss;
}

function ApplySAN(ss)
{ if (ss.length<6)
  { PieceName = "KQRBNP";
    if ((document.BoardForm)&&(document.BoardForm.SAN))
      document.BoardForm.SAN.value=PieceName;
  }
  else
  { PieceName = ss;
    if ((document.BoardForm)&&(document.BoardForm.SAN))
      document.BoardForm.SAN.value=ss;
  }
  for (var ii=0; ii<6; ii++) PieceCode[ii]=PieceName.charCodeAt(ii);
}

function ApplyFEN(ss)
{ if (ss.length==0)
  { FenString = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
    if ((document.BoardForm)&&(document.BoardForm.FEN))
      document.BoardForm.FEN.value=FenString;
  }
  else
  { FenString = ss;
    if ((BoardForm)&&(document.BoardForm.FEN))
      document.BoardForm.FEN.value=ss;
  }
}

function GetFEN()
{ var ii, jj, ee, ss="";
  for (jj=7; jj>=0; jj--)
  { ee=0;
    for (ii=0; ii<8; ii++)
    { if (Board[ii][jj]==0) ee++;
      else
      { if (ee>0)
        { ss=ss+""+ee;
          ee=0;
        }
        if (Board[ii][jj]>0) 
          ss=ss+PieceName.toUpperCase().charAt(Board[ii][jj]-1);
        else
          ss=ss+PieceName.toLowerCase().charAt(-Board[ii][jj]-1);
      }
    }
    if (ee>0) ss=ss+""+ee;
    if (jj>0) ss=ss+"/";
  }
  if (MoveType==0) ss=ss+" w";
  else ss=ss+" b";
  ee="";
  if ((Castling[0][0]>0)&&(PieceMoves[0][0]==0))
  { for (ii=0; ii<16; ii++)
    { if ((PieceType[0][ii]==2)&&(PiecePosX[0][ii]==7)&&(PiecePosY[0][ii]==0))
      ee=ee+PieceName.toUpperCase().charAt(0);
    }
  }
  if ((Castling[0][1]>0)&&(PieceMoves[0][0]==0))
  { for (ii=0; ii<16; ii++)
    { if ((PieceType[0][ii]==2)&&(PiecePosX[0][ii]==0)&&(PiecePosY[0][ii]==0))
      ee=ee+PieceName.toUpperCase().charAt(1);
    }
  }
  if ((Castling[1][0]>0)&&(PieceMoves[1][0]==0))
  { for (ii=0; ii<16; ii++)
    { if ((PieceType[1][ii]==2)&&(PiecePosX[1][ii]==7)&&(PiecePosY[1][ii]==7))
      ee=ee+PieceName.toLowerCase().charAt(0);
    }
  }
  if ((Castling[1][1]>0)&&(PieceMoves[1][0]==0))
  { for (ii=0; ii<16; ii++)
    { if ((PieceType[1][ii]==2)&&(PiecePosX[1][ii]==0)&&(PiecePosY[1][ii]==7))
      ee=ee+PieceName.toLowerCase().charAt(1);
    }
  }
  if (ee=="") ss=ss+" -";
  else ss=ss+" "+ee;
  if (MoveCount>StartMove)
  { CanPass=-1;
    ii=HistPiece[0][MoveCount-StartMove-1];
    if ((HistType[0][MoveCount-StartMove-1]==5)&&(Math.abs(HistPosY[0][MoveCount-StartMove-1]-PiecePosY[1-MoveType][ii])==2))
      CanPass=PiecePosX[1-MoveType][ii];
  }
  else
    CanPass=EnPass;
  if (CanPass>=0)
  { ss=ss+" "+String.fromCharCode(97+CanPass);
    if (MoveType==0) ss=ss+"6";
    else ss=ss+"3";
  }
  else ss=ss+" -";
  ss=ss+" "+HalfMove[MoveCount-StartMove];
  ss=ss+" "+Math.floor((MoveCount+2)/2);
  if ((document.BoardForm)&&(document.BoardForm.FEN))
    document.BoardForm.FEN.value=ss;
  return(ss);
}
function GetFENList()
{ var mmove=MoveCount, vvariant=CurVar, nn=0;
  var ff, ff_new, ff_old;
  isCalculating=true;
  ff=GetFEN();
  ff_new=ff;
  do
  { ff_old=ff_new;
    MoveBack(1);
    ff_new=GetFEN();
    if (ff_old!=ff_new) { ff=ff_new+"\n"+ff; nn++ }
  }
  while (ff_old!=ff_new);
  isCalculating=false;
  if (vvariant==0)
  { if (nn>0) MoveForward(nn); }
  else SetMove(mmove, vvariant);
  return(ff);
}
function SetTitle(tt)
{ top.document.title=tt;
}
function AddText(tt)
{ document.writeln(tt);
}
function EvalUrlString(ss)
{ var ii, jj, aa, uurl = window.location.search;
  if (uurl != "")
  { uurl = uurl.substring(1, uurl.length);
    while (uurl.indexOf("|")>0) uurl=uurl.replace("|","/");
    var llist = uurl.split("&");
    for (ii=0; ii<llist.length; ii++)
    { tt = llist[ii].split("=");
      aa=tt[1];
      for (jj=2; jj<tt.length; jj++) aa+="="+tt[jj];
      if (ss)
      { if (ss==tt[0]) eval(tt[0]+"('"+unescape(aa)+"')");
      }
      else eval(tt[0]+"('"+unescape(aa)+"')");
    }
  }
}
function SetMove(mmove, vvariant)
{ var ii=isCalculating;
  isCalculating=true;
  if (RecordCount>0) MoveBack(MaxMove);
  if (vvariant)
  { if (vvariant>=ShortPgnMoveText[0].length) { isCalculating=ii; return; }
    if (CurVar!=vvariant) 
    { SetMove(ShortPgnMoveText[2][vvariant], ShortPgnMoveText[1][vvariant]);
      CurVar=vvariant;
    }  
  }
  else
  { while (CurVar!=0) MoveBack(1);
  }  
  isCalculating=ii;
  var dd=mmove-MoveCount;
  if (dd<=0) MoveBack(-dd);
  else MoveForward(dd, 1);
  if (isCalculating) return;
  if ((document.BoardForm)&&(document.BoardForm.PgnMoveText))
    document.BoardForm.PgnMoveText.value=ShortPgnMoveText[0][CurVar];
  if (AutoPlayInterval) clearTimeout(AutoPlayInterval);
  if (isAutoPlay) AutoPlayInterval=setTimeout("MoveForward(1)", Delay);
}
function ApplyPgnMoveText(ss, rroot, ddocument, ggame)
{ var vv=0;
  if (! isNaN(rroot)) 
  { vv=ShortPgnMoveText[0].length; 
    ShortPgnMoveText[0][vv]=""; 
  }
  else 
  { ShortPgnMoveText[0].length=1;
    if (ddocument) TargetDocument=ddocument;
    else TargetDocument=window.document;
    if (rroot) activeAnchorBG=rroot;
    if (ggame) startAnchor=ggame;
    else startAnchor=-1;
  }  
  var ii, uu="", uuu="", cc, bb=0, bbb=0, ll=ss.length;
  for (ii=0; ii<ll; ii++)  
  { cc=ss.substr(ii,1);
    if (cc=="{") bbb++;
    if (cc=="}") bbb--; 
    if ((cc==")")||(cc=="]")) 
    { bb--;
      if (bb==0)
      { if (bbb==0) uu+=ApplyPgnMoveText(uuu, vv);
        else uu+=uuu;
        uuu="";
      }  
    }  
    if (bb==0) uu+=cc;
    else uuu+=cc;
    if ((cc=="(")||(cc=="[")) bb++; 
  }
  if (! isNaN(rroot))
  { ii=0, jj=0, bb=0;
    while ((ii<uu.length-1)&&(((ii>0)&&(uu.charAt(ii-1)!=" "))||(isNaN(parseInt(uu.charAt(ii)))))) ii++;
    while ((ii<uu.length-1)&&(! isNaN(parseInt(uu.charAt(ii))))) { bb=10*bb+parseInt(uu.charAt(ii)); ii++; }
    if (ii<uu.length-1)
    { uuu=uu.substr(ii, 3);
      switch (uuu)
      { case "...": jj=1; break;
        case " ..": jj=1; break;
      }
      if (jj==0)  
      { uuu=uu.substr(ii, 4);
        switch (uuu)
        { case "....": jj=1; break;
          case ". ..": jj=1; break;
          case " ...": jj=1; break;
        }
      }
      if (jj==0)  
      { uuu=uu.substr(ii, 5);
        if (uuu==". ...") jj=1;
      }
    }  
    bb=2*(bb-1)+jj;
    //if (bb<0) bb=MoveCount;
    SetPgnMoveText(uu, vv, rroot, bb);
  }
  else SetPgnMoveText(uu);
  return(vv);
}
function GetHTMLMoveText(vvariant, nnoscript, iitaliccomment)
{ var vv=0, tt, ii, uu="", uuu="", cc, bb=0, bbb=0;
  var ss="", sstart=0, nn=MaxMove, ffst=0,llst,ssearch,ssub,ffull,mmove0="",mmove1="", gg="";
  if (startAnchor!=-1) gg=",'"+startAnchor+"'";
  isCalculating=true;
  if (vvariant) 
  { vv=vvariant;
    if (! isNaN(ShortPgnMoveText[0][vv]))
    { SetMove(ShortPgnMoveText[0][vv], ShortPgnMoveText[1][vv]);
      CurVar=ShortPgnMoveText[1][vv];
      return(GetDiagram());
    }  
    if (ShortPgnMoveText[2][vv]<0) return(ShortPgnMoveText[0][vv]);
    SetMove(ShortPgnMoveText[2][vv], ShortPgnMoveText[1][vv]);
    CurVar=vvariant;
  }  
  else MoveBack(MaxMove);
  tt=ShortPgnMoveText[0][vv];
  
  ffull=Uncomment(ShortPgnMoveText[0][CurVar]);
  for (ii=0; (ii<nn)&&(ffst>=0)&&(MoveCount<MaxMove); ii++)
  { ssearch=Math.floor(MoveCount/2+2)+".";
    llst=ffull.indexOf(ssearch);
    ssearch=Math.floor(MoveCount/2+1)+".";
    ffst=ffull.indexOf(ssearch);
    if (ffst>=0)
    { ffst+=ssearch.length;
      if (llst<0)
        ssub=ffull.substring(ffst);
      else
        ssub=ffull.substring(ffst, llst);
      mmove0=GetMove(ssub,MoveType);
      if (mmove0!="")
      { if (ParseMove(mmove0, true)>0)
        { mmove1=mmove0;
          if (MoveType==0)
            HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+"."+mmove1;
          else
            HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+". ... "+mmove1;
          MoveCount++;
          MoveType=1-MoveType;
        }  
        else
        { if (MoveType==1)
          { ssub=Math.floor(MoveCount/2+1);
            ssearch=ssub+"....";
            ffst=ffull.indexOf(ssearch);
            if (ffst<0)
            { ssearch=ssub+"...";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+". ...";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+". ..";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+" ...";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst<0)
            { ssearch=ssub+" ..";
              ffst=ffull.indexOf(ssearch);
            }
            if (ffst>=0) 
            { ffst+=ssearch.length;
              if (llst<0)
                ssub=ffull.substring(ffst);
              else
                ssub=ffull.substring(ffst, llst);
              mmove0=GetMove(ssub,0);
              if (mmove0!="")
              { if (ParseMove(mmove0, true)>0)
                { mmove1=mmove0;
                  HistMove[MoveCount-StartMove]=Math.floor((MoveCount+2)/2)+". ... "+mmove1;
                  MoveCount++;
                  MoveType=1-MoveType;
                }  
                else
                { ffst=-1;
                  //alert(mmove0+" is not a valid move.");
                }
              }
            }
          }
          else
          { ffst=-1;
            //alert(mmove0+" is not a valid move.");
          }
        }
      }
      else ffst=-1;
    }
    if (mmove1!="")
    { sstart=-1;
      do sstart=tt.indexOf(mmove1, sstart+1);
      while ((sstart>0)&&(IsInComment(tt, sstart)));
      if (sstart>=0)
      { ss+=tt.substr(0,sstart);
        if (! nnoscript) ss+="<a href=\"javascript:SetMove{{"+MoveCount+","+vv+gg+"}}\" name=\"m"+MoveCount+"v"+vv+"\">";
        if (vv==0) ss+="<b>"
        ss+=mmove1;
        if (vv==0) ss+="</b>";
        if (! nnoscript) ss+="</a>";
        tt=tt.substr(sstart+mmove1.length);
      }
      else ffst=-1;
    }
  }  
  ss+=tt;

  var ll=ss.length;
  for (ii=0; ii<ll; ii++)  
  { cc=ss.substr(ii,1);
    if (cc=="{") bbb++;
    if (cc=="}") bbb--; 
    if ((cc==")")||(cc=="]")) 
    { bb--;
      if (bb==0)
      { if (bbb==0)
        { if (! isNaN(ShortPgnMoveText[0][uuu]))
          { cc=uu.length-1;
            uu=uu.substr(0,cc);
            cc="";
          }
          uu+=GetHTMLMoveText(uuu, nnoscript);
        }
        else uu+=uuu;
        uuu="";
      }  
    }  
    if (bb==0) uu+=cc;
    else uuu+=cc;
    if ((cc=="(")||(cc=="[")) bb++; 
  }  
   
  if (! vvariant) 
  { MoveBack(MaxMove);
    tt=uu.split("{{");
    ll=tt.length;
    uu=tt[0];
    for (ii=1; ii<ll; ii++) uu+="("+tt[ii];
    tt=uu.split("}}");
    ll=tt.length;
    uu=tt[0];
    for (ii=1; ii<ll; ii++) uu+=")"+tt[ii];
    if (iitaliccomment)
    { tt=uu.split("{");
      ll=tt.length;
      uu=tt[0];
      for (ii=1; ii<ll; ii++) uu+="<i>"+tt[ii];
      tt=uu.split("}");
      ll=tt.length;
      uu=tt[0];
      for (ii=1; ii<ll; ii++) uu+="</i>"+tt[ii];    	
    }
  }
  isCalculating=false;   
  return(uu);
}
function IsInComment(ss, nn)
{ var ii=-1, bb=0;
  do { ii=ss.indexOf("{",ii+1); bb++; }  
  while ((ii>=0)&&(ii<nn));
  ii=-1;
  do { ii=ss.indexOf("}",ii+1); bb--; }  
  while ((ii>=0)&&(ii<nn));  
  return(bb);
}
function HighlightMove(nn)
{ var ii, cc, bb, jj=0, ll=TargetDocument.anchors.length;
  if (ll==0) return;
  if (! TargetDocument.anchors[0].style) return;
  if ((activeAnchor>=0)&&(ll>activeAnchor))
  { TargetDocument.anchors[activeAnchor].style.backgroundColor="";
    activeAnchor=-1;
  }
  if (isNaN(startAnchor))
  { while ((jj<ll)&&(TargetDocument.anchors[jj].name!=startAnchor)) jj++;
  }
  for (ii=jj; ((ii<ll)&&(activeAnchor<0)); ii++)
  { if (TargetDocument.anchors[ii].name==nn)
    { activeAnchor=ii;
      TargetDocument.anchors[activeAnchor].style.backgroundColor=activeAnchorBG;
      return;
    }
  }
}
function GetDiagram()
{ var ii, jj, ss=String.fromCharCode(13)+"<P align=center>";
  ss+="<table noborder cellpadding=1 cellspacing=0><tr><th bgcolor=#808080>";
  ss+="<TABLE noborder cellpadding=0 cellspacing=0 background='bw.gif'><TR><TD>";
  var tt=new Array("k","q","r","b","n","p");
  if (isRotated)
  { for (jj=0; jj<8; jj++)
    { ss+="<NOBR>";
      for (ii=7; ii>=0; ii--)
      { if (Board[ii][jj]==0)
          ss+="<IMG SRC='"+ColorName[(ii+jj+1)%2]+".gif'>";
        else
          ss+="<IMG SRC='"+ColorName[(1-sign(Board[ii][jj]))/2]+tt[Math.abs(Board[ii][jj])-1]+ColorName[(ii+jj+1)%2]+".gif'>";
      }
      ss+="</NOBR>";
      if (jj<7) ss+="<BR />";
    }
  }
  else
  { for (jj=7; jj>=0; jj--)
    { ss+="<NOBR>";
      for (ii=0; ii<8; ii++)
      { if (Board[ii][jj]==0)
          ss+="<IMG SRC='"+ColorName[(ii+jj+1)%2]+".gif'>";
        else
          ss+="<IMG SRC='"+ColorName[(1-sign(Board[ii][jj]))/2]+tt[Math.abs(Board[ii][jj])-1]+ColorName[(ii+jj+1)%2]+".gif'>";
      }
      ss+="</NOBR>";
      if (jj>0) ss+="<BR />";
    }
  }
  ss+="</TD></TR></TABLE>";
  if (IsLabelVisible)
  { if (isRotated)
    { ss+="</th><th><img src='1_8.gif'></th></tr>";
      ss+="<tr><th><img src='h_a.gif'></th>";
      ss+="<th><img src='1x1.gif'></th></tr></table>";
    }
    else
    { ss+="</th><th><img src='8_1.gif'></th></tr>";
      ss+="<tr><th><img src='a_h.gif'></th>";
      ss+="<th><img src='1x1.gif'></th></tr></table>";
    }  
  }
  else ss+="</th></tr></table>";    
  ss+="</P>"+String.fromCharCode(13);
  return (ss);
}
function SwitchSetupBoard()
{ BoardClicked=-1;
  if (!isSetupBoard)
  { Init('standard');
    isSetupBoard=true;
    if ((document.BoardForm)&&(document.BoardForm.SetupBoard))
      document.BoardForm.SetupBoard.value=" Ready ";
    return;
  }
  isSetupBoard=false;
  if ((document.BoardForm)&&(document.BoardForm.SetupBoard))
    document.BoardForm.SetupBoard.value="Setup Board";
  var ii, jj, ee, ss="";
  for (jj=7; jj>=0; jj--)
  { ee=0;
    for (ii=0; ii<8; ii++)
    { if (Board[ii][jj]==0) ee++;
      else
      { if (ee>0)
        { ss=ss+""+ee;
          ee=0;
        }
        if (Board[ii][jj]>0) 
          ss=ss+PieceName.toUpperCase().charAt(Board[ii][jj]-1);
        else
          ss=ss+PieceName.toLowerCase().charAt(-Board[ii][jj]-1);
      }
    }
    if (ee>0) ss=ss+""+ee;
    if (jj>0) ss=ss+"/";
  }
  MoveType=-1;
  while (MoveType<0)
  { if (MoveType<0)
    { if (confirm("White to move?")) MoveType=0;
    }
    if (MoveType<0)
    { if (confirm("Black to move?")) MoveType=1;
    }
  }
  if (MoveType==0) ss=ss+" w";
  else ss=ss+" b";
  ss=ss+" KQkq";
  ss=ss+" -";
  ss=ss+" "+HalfMove[MoveCount-StartMove];
  ss=ss+" "+Math.floor((MoveCount+2)/2);
  if ((document.BoardForm)&&(document.BoardForm.FEN))
    document.BoardForm.FEN.value=ss;    
  Init(ss);
}
function SetBoardSetupMode(mm)
{ BoardSetupMode=mm;
  BoardClicked=-1;
}
function SetupBoardClick(nn)
{ var ii, jj, ii0, jj0, ii1, jj1, mm, nnn;
  if (isRotated) nnn=63-nn;
  else nnn=nn;
  if ((BoardClicked<0)&&(BoardSetupMode!='delete'))
  { if (nn>=64) { BoardClicked=nn; return; }
    ii0=nnn%8;
    jj0=7-(nnn-ii0)/8;
    if (Board[ii0][jj0]!=0) BoardClicked=nnn; 
    return; 
  }
  if (BoardClicked>=0)
  { ii0=BoardClicked%8;
    jj0=7-(BoardClicked-ii0)/8;
  }
  ii1=nnn%8;
  jj1=7-(nnn-ii1)/8;
  if (((Board[ii1][jj1]!=0))&&(BoardSetupMode!='delete')) 
  { BoardClicked=nnn; 
    return;
  }
  if (BoardSetupMode=='copy')
  { Board[ii1][jj1]=Board[ii0][jj0];
    BoardClicked=nnn;
  }
  if (BoardSetupMode=='move')
  { if (BoardClicked>=64)
    { ii0=BoardClicked%2;
      jj0=(BoardClicked-64-ii0)/2;
      if (ii0==0) Board[ii1][jj1]=jj0+1;
      else Board[ii1][jj1]=-jj0-1;
    }
    else
    { Board[ii1][jj1]=Board[ii0][jj0];
      Board[ii0][jj0]=0;
      BoardClicked=nnn;
    }  
  }
  if (BoardSetupMode=='delete')
  { Board[ii1][jj1]=0;
    BoardClicked=-1;
  }
  if (isRotated)
  { for (ii=0; ii<8; ii++)
    { for (jj=0; jj<8; jj++)
      { if (Board[ii][jj]==0)
          SetImg(63-ii-(7-jj)*8,BoardPic[(ii+jj+1)%2].src);
        else
          SetImg(63-ii-(7-jj)*8,PiecePic[(1-sign(Board[ii][jj]))/2][Math.abs(Board[ii][jj])-1][(ii+jj+1)%2].src);
      }
    }
  }
  else
  { for (ii=0; ii<8; ii++)
    { for (jj=0; jj<8; jj++)
      { if (Board[ii][jj]==0)
          SetImg(ii+(7-jj)*8,BoardPic[(ii+jj+1)%2].src);
        else
          SetImg(ii+(7-jj)*8,PiecePic[(1-sign(Board[ii][jj]))/2][Math.abs(Board[ii][jj])-1][(ii+jj+1)%2].src);
      }
    }
  }  
}
function ParsePgn(nn)
{ if (! parent.frames[1].document.documentElement) 
  { if (nn>-20) setTimeout("ParsePgn("+eval(nn-2)+")",400); 
    return; 
  } 
  var ss=parent.frames[1].document.documentElement.innerHTML;
  var ii, jj, ll=0, tt; 
  if (ss!="") ll=ss.length;
  if (ll!=nn)
  { setTimeout("ParsePgn("+ll+")",400);
    return;
  }
  if (ll==0) return;
  ss=ss.replace(/\<html\>/i,'');  
  ss=ss.replace(/\<\/html\>/i,'');
  ss=ss.replace(/\<head\>/i,'');  
  ss=ss.replace(/\<\/head\>/i,'');  
  ss=ss.replace(/\<body\>/i,'');  
  ss=ss.replace(/\<\/body\>/i,'');
  ss=ss.replace(/\<pre\>/i,'');  
  ss=ss.replace(/\<\/pre\>/i,'');  
  ss=ss.replace(/\<xmp\>/i,'');  
  ss=ss.replace(/\<\/xmp\>/i,'');    
  ss=ss.replace(/&quot;/g,'"');
//  while (ss.indexOf('&quot;')>0) ss=ss.replace('&quot;','"');
  ss=" "+ss;
  ss = ss.split("[");
  if (ss.length<2) return;
  tt=new Array(ss.length-1);
  for (ii=1; ii<ss.length; ii++)
    tt[ii-1]=ss[ii].split("]");
  var bblack=new Array();
  var wwhite=new Array();
  var rresult=new Array();
  var ppgnText=new Array();
  var ggameText=new Array();
  var ffenText=new Array();
  var kk, ff;
  jj=0;
  ffenText[jj]="";
  ggameText[jj]="";
  for (ii=0; ii<tt.length; ii++)
  { if (tt[ii][0].substr(0,6)=="Black ")
      bblack[jj]=tt[ii][0].substr(6,tt[ii][0].length);      
    if (tt[ii][0].substr(0,6)=="White ")
      wwhite[jj]=tt[ii][0].substr(6,tt[ii][0].length);
    if (tt[ii][0].substr(0,7)=="Result ")
      rresult[jj]=tt[ii][0].substr(7,tt[ii][0].length);
    if (tt[ii][0].substr(0,4)=="FEN ")
      ffenText[jj]=tt[ii][0].substr(4,tt[ii][0].length);      
    ggameText[jj]+="["+tt[ii][0]+"]<br />";
    kk=0;    
    while ((kk<tt[ii][1].length)&&(tt[ii][1].charCodeAt(kk)<49)) kk++; 
    if (kk<tt[ii][1].length)
    { ppgnText[jj]=tt[ii][1].substr(kk,tt[ii][1].length);
      kk=0; ff=String.fromCharCode(13);
      while ((kk=ppgnText[jj].indexOf(ff, kk))>0) ppgnText[jj]=ppgnText[jj].substr(0,kk)+""+ppgnText[jj].substr(kk+1);
      kk=0; ff=String.fromCharCode(10)+String.fromCharCode(10);
      while ((kk=ppgnText[jj].indexOf(ff, kk))>0) ppgnText[jj]=ppgnText[jj].substr(0,kk)+" <br /><br /> "+ppgnText[jj].substr(kk+2);    
      kk=0; ff=String.fromCharCode(10);
      while ((kk=ppgnText[jj].indexOf(ff, kk))>0) ppgnText[jj]=ppgnText[jj].substr(0,kk)+" "+ppgnText[jj].substr(kk+1);    
      ppgnText[jj]=escape(ppgnText[jj]);
      while (ffenText[jj].indexOf('"')>=0) ffenText[jj]=ffenText[jj].replace('"','');
      ffenText[jj]=escape(ffenText[jj]);  
      ggameText[jj]=escape(ggameText[jj]);
      jj++;
      ffenText[jj]="";
      ggameText[jj]="";
    }
  }
  var dd=parent.frames[1].document;
  dd.open();
  dd.writeln("<html><head>");
  dd.writeln("<style type='text/css'>");
  dd.writeln("body { background-color:#E0C8A0;color:#000000;font-size:10pt;line-height:12pt;font-family:Verdana; }");
  dd.writeln("a {color:#000000; text-decoration: none}");
  dd.writeln("a:hover {color:#FFFFFF; background-color:#806040}");
  dd.writeln("</style>");
  dd.writeln("<"+"script language='JavaScript'>");
  dd.writeln("if (! parent.frames[0]) location.href='ltpgnviewer.html?'+location.href;");
  dd.writeln("var PgnMoveText=new Array();");
  dd.writeln("var GameText=new Array();");    
  dd.writeln("var FenText=new Array();");    
  for (ii=0; ii<jj; ii++)
  { dd.writeln("PgnMoveText["+ii+"]='"+ppgnText[ii]+"';");
    dd.writeln("GameText["+ii+"]='"+ggameText[ii]+"';");
    if (ffenText[ii]!="") dd.writeln("FenText["+ii+"]='"+ffenText[ii]+"';");
  }
  dd.writeln("function OpenGame(nn)");
  dd.writeln("{ if (parent.frames[0].IsComplete)");
  dd.writeln("  { if (parent.frames[0].IsComplete())");
  dd.writeln("    { if (nn>=0)");
  dd.writeln("      { if (FenText[nn]) parent.frames[0].Init(unescape(FenText[nn]));");
  dd.writeln("        else parent.frames[0].Init('');");    
  dd.writeln("        //parent.frames[0].SetPgnMoveText(unescape(PgnMoveText[nn])); //variants not possible");
  dd.writeln("        parent.frames[0].ApplyPgnMoveText(unescape(PgnMoveText[nn]),'#CCCCCC',window.document); //variants possible");
  dd.writeln("        //document.getElementById('GameText').innerHTML=unescape(GameText[nn])+'<br />'+PgnMoveText[nn]; //pgn without html links");
  dd.writeln("        document.getElementById('GameText').innerHTML=unescape(GameText[nn])+'<br />'+parent.frames[0].GetHTMLMoveText(0,false,true); //pgn with html links");
  dd.writeln("      }");    
  dd.writeln("      return;");
  dd.writeln("    }");
  dd.writeln("  }");
  dd.writeln("  setTimeout('OpenGame('+nn+')',400);");    
  dd.writeln("}");
  dd.writeln("function SetMove(mm,vv){ if (parent.frames[0].SetMove) parent.frames[0].SetMove(mm,vv); }");   
  if (jj>1)
  { dd.writeln("function SearchGame()");
    dd.writeln("{ var tt=document.forms[0].SearchText.value;");
    dd.writeln("  if (tt=='') return;");
    dd.writeln("  var ll=document.forms[0].GameList;");
    dd.writeln("  var ii, jj=ll.selectedIndex, kk=ll.options.length;");
    dd.writeln("  for (ii=1; ii<kk; ii++)");
    dd.writeln("  { if (ll.options[(ii+jj)%kk].text.indexOf(tt)>=0)");
    dd.writeln("    { ll.selectedIndex=(ii+jj)%kk;");
    dd.writeln("      OpenGame(ll.options[(ii+jj)%kk].value);");
    dd.writeln("      return;");
    dd.writeln("    }");
    dd.writeln("  }");
    dd.writeln("}");
  }
  dd.writeln("</"+"script>");
  if (jj==1) dd.writeln("</head><body onLoad=\"setTimeout('OpenGame(0)',400)\">");
  else 
  { dd.writeln("</head><body>");
    dd.writeln("<FORM><NOBR><SELECT name='GameList' onChange='OpenGame(this.options[selectedIndex].value)' SIZE=1>");
    dd.writeln("<OPTION VALUE=-1>Select a game !");
    for (ii=0; ii<jj; ii++)
      dd.writeln("<OPTION VALUE="+ii+">"+wwhite[ii].replace(/"/g,'')+" - "+bblack[ii].replace(/"/g,'')+" "+rresult[ii].replace(/"/g,''));
    dd.writeln("</SELECT>");
    if (jj<24) dd.writeln("<!--");
    dd.writeln("<INPUT name='SearchText' size=12><INPUT type='button' value='search' onClick='SearchGame()'>");
    if (jj<24) dd.writeln("//-->");  
    dd.writeln("</NOBR></FORM>"); 
  }
  dd.writeln("<div id='GameText'>");
  dd.writeln("</div><!--generated with LT-PGN-VIEWER 2.6--></body></html>");
  dd.close();
}
function IsComplete()
{ return(isInit);
}