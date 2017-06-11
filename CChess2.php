<?php

/// <summary>
/// Mappings act as lookup tables to easily check if a character is allowed to be part of
/// a set. For example the move symbol can only be composed of characters:
///  1-8, A-G, a-g, K,k, N,n, Q,q, R,r, X,x, =, -, # and +
/// The characters will be mapped to a value like 1, while the rest will be 0.
/// Then to check if a character is acceptable for a move symbol one simply does:
/// intVal = myString[i];
/// If(symbolMapping[intVal] == 1) {//accepted} else {//rejected}
/// </summary>
class MAPPINGS
{
	/// <summary>
	/// Move Symbol mapping: 1-8, a-g, B, K, N, O, Q, R, p (en passant), x, =, -, # and +
	/// </summary>
	public static $MoveSymbolMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  " [#] $  %  &  '  (  )  * [+] , [-] .  /  0 [1  2  3  4  5  6  7  8] 9  :  ;  < [=] >  ?
		  0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0,
		//@  A [B] C  D  E  F  G  H  I  J [K] L  M [N  O] P [Q  R] S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//` [a  b  c  d  e  f  g  h] i  j  k  l  m  n  o [p] q  r  s  t  u  v  w [x] y  z  {  |  }  ~ DEL
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Mapping of upper and lower case letters for the Move Symbol: A-G, a-g, K,k, N,n, Q,q, R,r, P,p X,x
	/// </summary>
	public static $LetterMoveSymbolMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@ [A  B  C  D  E  F  G  H] I  J [K] L  M [N  O  P  Q  R] S  T  U  V  W [X] Y  Z  [  \  ]  ^  _
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0,
		//` [a  b  c  d  e  f  g  h] i  j [k] l  m [n  o  p  q  r] s  t  u  v  w [x] y  z  {  |  }  ~ DEL
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Mapping for Tag names: A-Z, a-z, 0-9 and _
	/// </summary>
	public static $TagNameMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Mapping of alphabet: A-Z, a-z
	/// </summary>
	public static $AlphabetMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Mapping of upper case alphabet: A-Z
	/// </summary>
	public static $AlphabetUpperMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@ [A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z] [  \  ]  ^  _
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Mapping of lower case alphabet: a-z
	/// </summary>
	public static $AlphabetLowerMapping = array(
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@ [A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z] [  \  ]  ^  _
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Number mapping: 0-9
	/// </summary>
	public static $NumberMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Mapping of letters and numbers: A-Z, a-z and 0-9
	/// </summary>
	public static $LetterNumberMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// NewLine mapping: '\n' '\r'
	/// </summary>
	public static $NewLineMapping = array(
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Space character and new line characters mapping: ' ', '\n', '\r'
	/// </summary>
	public static $SpaceNewLineMapping = array(
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Tab character and new line characters mapping: '\t', '\n', '\r'
	/// </summary>
	public static $TabNewLineMapping = array(
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// Printable characters that can appear in a string. Includes extended ASCII chars from  128 to 168.
	/// </summary>
	public static $StringCharactersMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0,
		//EXTENDED ASCII is left as 0
		  1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
		  1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

	/// <summary>
	/// To line end comment and escape character: ';' and '%'
	/// </summary>
	public static $EndToLineIgnoreCharsMapping = array( 
		//0  1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//   !  "  #  $  %  &  '  (  )  *  +  ,  -  .  /  0  1  2  3  4  5  6  7  8  9  :  ;  <  =  >  ?
		  0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0,
		//@  A  B  C  D  E  F  G  H  I  J  K  L  M  N  O  P  Q  R  S  T  U  V  W  X  Y  Z  [  \  ]  ^  _
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//`  a  b  c  d  e  f  g  h  i  j  k  l  m  n  o  p  q  r  s  t  u  v  w  x  y  z  {  |  }  ~ DEL
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		//EXTENDED ASCII is left as 0
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
		  0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
	);

}

// Implements a bitboard that is 64bit and 32bit compatible. 32 bit systems will use two 32 bit integers.
class BitBoardOLD
{
	public $num64;
	public $low32;		// stores the half with the low order bits 0 - 2^32 - 1
	public $high32;	// stores the half with the high order bits 2^32 - 2^64 - 1
	
	public function BitBoard($hex = "")
	{
		$this->num64 = 0;
		$this->low32 = 0;
		$this->high32 = 0;
		if($hex != "")
			$this->set_value($hex);
	}

	public function shift_r($num)
	{
		if(PHP_INT_SIZE == 4)
		{
			// generate a 64 bit binary string from the high and low numbers. Then get a substring
			// from 0 to 64 - $num and zero pad on the left. Then split the string and convert back to base10.
			$bin_l = base_convert($this->low32, 10, 2);
			$bin_h = base_convert($this->high32, 10, 2);
			//echo "bin_h : $bin_h and bin_l : $bin_l";
			$full = str_pad($bin_h, 32, '0', STR_PAD_LEFT) . str_pad($bin_l, 32, '0', STR_PAD_LEFT);
			//echo "<Br/>Full is $full";
			$new = str_pad(substr($full, 0, 64 - $num), 64, '0', STR_PAD_LEFT);
			//echo "<br/>New is $new";
			$bin_h = substr($new, 0, 32);
			$bin_l = substr($new, 32);
			//echo "<br/>l = $bin_l, h = $bin_h";
			$this->low32 = (int)base_convert($bin_l, 2, 10);
			$this->high32 = (int)base_convert($bin_h, 2, 10);
		}
		else
		{
			$this->num64 = $this->num64 >> $num;
		}
		return $this;
	}
	
	// Performs the right shift and returns a new BitBoard object. Does not alter this bitboard object.
	public function shift_r_($num)
	{
		$bb = new BitBoard();
		$bb->num64 = $this->num64;
		$bb->low32 = $this->low32;
		$bb->high32 = $this->high32;
		$bb->shift_r($num);
		return $bb;
	}
	
	public function shift_l($num)
	{
		if(PHP_INT_SIZE == 4)
		{
			// // generate a 64 bit binary string from the high and low numbers. Then get a substring
			// // from $num and zero pad on the right. Then split the string and convert back to base10.
			// $bin_l = base_convert($this->low32, 10, 2);
			// $bin_h = base_convert($this->high32, 10, 2);
			// $full = str_pad($bin_h, 32, '0', STR_PAD_LEFT) . str_pad($bin_l, 32, '0', STR_PAD_LEFT);
			// echo "<Br/>Full is $full";
			// $new = str_pad(substr($full, $num), 64, '0');
			// echo "<br/>New is $new";
			// $bin_h = substr($new, 0, 32);
			// $bin_l = substr($new, 32);
			// echo "<br/>l = $bin_l, h = $bin_h";
			// $this->low32 = (int)base_convert($bin_l, 2, 10);
			// $this->high32 = (int)base_convert($bin_h, 2, 10);
			
			$num1 = $this->high32 >> 16;
			$num2 = $this->high32 & 0x0000FFFF;
			$num3 = $this->low32 >> 16;
			$num4 = $this->low32 & 0x0000FFFF;
			var_dump($num1);
			var_dump($num2);
			var_dump($num3);
			var_dump($num4);
			if($num >= 48)
			{
				$num -= 48;
				$num1 = $num4; $num2 = $num3 = $num4 = 0;
			}
			else if($num >= 32)
			{
				$num -= 32;
				$num1 = $num3; $num2 = $num4; $num3 = $num4 = 0;
			}
			else if($num >= 16)
			{
				$num -= 16;
				$num1 = $num2; $num2 = $num3; $num3 = $num4; $num4 = 0;
			}
			if($num == 16 || $num == 32 || $num == 48)
			{
				//$this->num1 = $num1; $this->num2 = $num2; $this->num3 = $num3; $this->num4 = $num4;
			}
			else
			{
				$tmp1 = $num1 << $num; $tmp2 = $num2 << $num; $tmp3 = $num3 << $num; $tmp4 = $num4 << $num;
				$num4 = $tmp4 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
				$num3 = $tmp3 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
				$num3 |= ($tmp4 >> 16);	// apply overflow from previous group to this group
				$num2 = $tmp2 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
				$num2 |= ($tmp3 >> 16);	// apply overflow from previous group to this group
				$num1 = $tmp1 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
				$num1 |= ($tmp2 >> 16);	// apply overflow from previous group to this group
			}
			
			$this->high32 = ($num1 << 16) | $num2;
			$this->low32 = ($num3 << 16) | $num4;
		}
		else
		{
			$this->num64 = $this->num64 << $num;
		}
		return $this;
	}
	// Performs the left shift and returns a new BitBoard object. Does not alter this bitboard object.
	public function shift_l_($num)
	{
		$bb = new BitBoard();
		$bb->num64 = $this->num64;
		$bb->low32 = $this->low32;
		$bb->high32 = $this->high32;
		$bb->shift_l($num);
		return $bb;
	}
	
	public function _AND($bb)
	{
		if(PHP_INT_SIZE == 4)
		{
			$this->low32 &= $bb->low32;
			$this->high32 &= $bb->high32;
		}
		else
		{
			$this->num64 &= $bb->num64;
		}
		return $this;
	}
	// Performs the AND and returns a new BitBoard object. Does not alter this bitboard object.
	// public function _AND_($bb)
	// {
		// $bb2 = new BitBoard();
		// $bb2->num64 = $this->num64;
		// $bb2->low32 = $this->low32;
		// $bb2->high32 = $this->high32;
		// $bb2->_AND($bb);
		// return $bb2;
	// }
	
	public function _OR($bb)
	{
		if(PHP_INT_SIZE == 4)
		{
			$this->low32 |= $bb->low32;
			$this->high32 |= $bb->high32;
		}
		else
		{
			$this->num64 |= $bb->num64;
		}
		return $this;
	}
	// Performs the OR and returns a new BitBoard object. Does not alter this bitboard object.
	// private function __OR($bb)
	// {
		// $bb2 = new BitBoard();
		// $bb2->num64 = $this->num64;
		// $bb2->low32 = $this->low32;
		// $bb2->high32 = $this->high32;
		// $bb2->_OR($bb);
		// return $bb2;
	// }
	
	public function _XOR($bb)
	{
		if(PHP_INT_SIZE == 4)
		{
			$this->low32 ^= $bb->low32;
			$this->high32 ^= $bb->high32;
		}
		else
		{
			$this->num64 ^= $bb->num64;
		}
		return $this;
	}
	// Performs the XOR and returns a new BitBoard object. Does not alter this bitboard object.
	// private function __XOR($bb)
	// {
		// $bb2 = new BitBoard();
		// $bb2->num64 = $this->num64;
		// $bb2->low32 = $this->low32;
		// $bb2->high32 = $this->high32;
		// $bb2->_XOR($bb);
		// return $bb2;
	// }
	
	public function set_value($hex)
	{
		if(PHP_INT_SIZE == 4)
		{
			$l = 0; $h = 0;
			if(strlen($hex) > 8)
			{
				$l = substr($hex, strlen($hex) - 8, 8);
				$h = substr($hex, 0, strlen($hex) - 8);
			}
			else
			{
				$l = $hex;
			}
			$this->low32 = (int)base_convert($l, 16, 10);
			$this->high32 = (int)base_convert($h, 16, 10);
		}
		else
		{
			$this->num64 = (int)base_convert($hex, 16, 10);
		}
	}
	
	// ANDs two bitboards and return a new bitboard instance with the result.
	public static function _AND_($bb1, $bb2)
	{
		return $bb1->duplicate()->_AND($bb2);
	}
	// ORs two bitboards and return a new bitboard instance with the result.
	public static function _OR_($bb1, $bb2)
	{
		return $bb1->duplicate()->_OR($bb2);
	}
	// XORs two bitboards and return a new bitboard instance with the result.
	public static function _XOR_($bb1, $bb2)
	{
		return $bb1->duplicate()->_XOR($bb2);
	}
	
	public function is_equal($bb)
	{
		if(PHP_INT_SIZE == 4)
		{
			return $this->low32 == $bb->low32 && $this->high32 == $bb->high32;
		}
		else
		{
			return $this->num64 == $bb->num64;
		}
	}
	
	public function is_zero()
	{
		if(PHP_INT_SIZE == 4)
		{
			return $this->low32 == 0 && $this->high32 == 0;
		}
		else
		{
			return $this->num64 == 0;
		}
	}
	
	public function get_as_hex()
	{
		if(PHP_INT_SIZE == 4)
		{
			return base_convert($this->high32, 10, 16) . str_pad(base_convert($this->low32, 10, 16), 8, '0', STR_PAD_LEFT);
		}
		else
		{
			return base_convert($this->num64, 10, 16);
		}
	}
	
	public function duplicate()
	{
		$new = new BitBoard();
		$new->low32 = $this->low32;
		$new->high32 = $this->high32;
		$new->num64 = $this->num64;
		return $new;
	}
	
	/// <summary>
	/// Find the position of the first bit set to 1 in a 64bit number, going from the LSB to the MSB
	/// </summary>
	/// <returns>Returns the position of the first bit set to 1. If no 1bits then 63 is returned.</returns>
	public function get_pos_of_first_one_bit()
	{
		if(PHP_INT_SIZE == 4)
		{
			for($i = 0; $i < 32; $i++)
			{
				if(($this->low32 & 1 << $i) != 0)
					return $i;
			}
			for($i = 0; $i < 32; $i++)
			{
				if(($this->high32 & 1 << $i) != 0)
					return $i + 32;
			}
		}
		else
		{
			for($i = 0; $i < 64; $i++)
			{
				if(($this->num64 & 1 << $i) != 0)
					return $i;
			}
		}
		return 63;
	}
	
	/// <summary>
	/// Find the position of the last 1 bit in a 64bit number, going from the LSB to the MSB.
	/// </summary>
	/// <returns>Returns the location of the last bit set to 1. If no bits are one then 0 is returned.</returns>
	public function get_pos_of_last_one_bit()
	{
		if(PHP_INT_SIZE == 4)
		{
			for($i = 31; $i > -1; $i--)
			{
				if(($this->high32 & 1 << $i) != 0)
					return $i + 32;
			}
			for($i = 31; $i > -1; $i--)
			{
				if(($this->low32 & 1 << $i) != 0)
					return $i;
			}
		}
		else
		{
			for($i = 63; $i > -1; $i--)
			{
				if(($this->num64 & 1 << $i) != 0)
					return $i;
			}
		}
		return 0;
	}
	
	/// <summary>
	/// For each 1 bit found converts that to a tile number and then returns all tile numbers found.
	/// </summary>
	/// <returns>Returns an array of tile numbers.</returns>
	public function get_pos_of_all_one_bits()
	{
		$positions = array();
		if(PHP_INT_SIZE == 4)
		{
			for($i = 0; $i < 32; $i++)
			{
				if(($this->low32 & 1 << $i) != 0)
					$positions[] = $i;
			}
			for($i = 0; $i < 32; $i++)
			{
				if(($this->high32 & 1 << $i) != 0)
					$positions[] = $i + 32;
			}
		}
		else
		{
			for($i = 0; $i < 64; $i++)
			{
				if(($this->num64 & 1 << $i) != 0)
					$positions[] = $i;
			}
		}
		return $positions;
	}
	
	
	public function print_nice()
	{
		echo '<pre>';
		if(PHP_INT_SIZE == 4)
		{
			$bin_l = base_convert($this->low32, 10, 2);
			$bin_h = base_convert($this->high32, 10, 2);
			$bin = str_pad($bin_h, 32, '0', STR_PAD_LEFT) . str_pad($bin_l, 32, '0', STR_PAD_LEFT);
		}
		else
		{
			$bin = str_pad(base_convert($this->num64, 10, 2), 64, '0', STR_PAD_LEFT);
		}
		for($row = 0; $row < 8; $row++)
		{
			for($col = 0; $col < 8; $col++)
			{
				echo $bin[$row * 8 + $col] . ' ';
			}
			echo "\n";
		}
		echo '</pre>';
	}
	
	public function debug()
	{
		echo '<pre>'; var_dump($this); echo '</pre>';
	}
	
}

class BitBoard
{
//PHP_INT_SIZE
	public $num1;	// high
	public $num2;
	public $num3;
	public $num4;	// low
	
	public static $calls = 0;
	public static $clones = 0;
	
	public function __construct($hex = "")
	{
		BitBoard::$calls++;
		$this->num1 = 0;
		$this->num2 = 0;
		$this->num3 = 0;
		$this->num4 = 0;
		if(strlen($hex) > 2)
		{
			if($hex[0] == '0' && $hex['1'] == 'x')
				$hex = substr($hex, 2);
		}
		if($hex != "")
			$this->set_value($hex);
	}

	public function shift_r($num)
	{
		//BitBoard::$calls++;
		// TODO: work out how to shift right using >> operator. How to deal with overflow to the next integer group?
		// generate a 64 bit binary string from the individual numbers. Then get a substring
		// from 0 to 64 - $num and zero pad on the left. Then split the string and convert back to base10.
		$bin1 = base_convert($this->num1, 10, 2);
		$bin2 = base_convert($this->num2, 10, 2);
		$bin3 = base_convert($this->num3, 10, 2);
		$bin4 = base_convert($this->num4, 10, 2);
		// echo "<br/>1 = $bin1, 2 = $bin2, 3 = $bin3, 4 = $bin4";
		$full = str_pad($bin1, 16, '0', STR_PAD_LEFT) . str_pad($bin2, 16, '0', STR_PAD_LEFT) . str_pad($bin3, 16, '0', STR_PAD_LEFT) . str_pad($bin4, 16, '0', STR_PAD_LEFT);
		// echo "<Br/>Full is $full";
		$new = str_pad(substr($full, 0, 64 - $num), 64, '0', STR_PAD_LEFT);
		// echo "<br/>New is $new";
		$bin1 = substr($new, 0, 16);
		$bin2 = substr($new, 16, 16);
		$bin3 = substr($new, 32, 16);
		$bin4 = substr($new, 48, 16);
		// echo "<br/>1 = $bin1, 2 = $bin2, 3 = $bin3, 4 = $bin4";
		$this->num1 = (int)base_convert($bin1, 2, 10);
		$this->num2 = (int)base_convert($bin2, 2, 10);
		$this->num3 = (int)base_convert($bin3, 2, 10);
		$this->num4 = (int)base_convert($bin4, 2, 10);
	
		return $this;
	}
	
	// Performs the right shift and returns a new BitBoard object. Does not alter this bitboard object.
	public function shift_r_($num)
	{
		$bb = new BitBoard();
		$bb->num1 = $this->num1;
		$bb->num2 = $this->num2;
		$bb->num3 = $this->num3;
		$bb->num4 = $this->num4;
		$bb->shift_r($num);
		return $bb;
	}
	
	public function shift_l($num)
	{
		//BitBoard::$calls++;
		if($num <= 0) return;	// Cannot do negative shifts.
		// Effectively the shifting is done in two stages. The first stage shifts bits by 16, 32 or 48 places.
		// The shift amount is then adjusted to perform the remaining shift. This reduces the amount of code.
		$tmp1 = $this->num1;
		$tmp2 = $this->num2;
		$tmp3 = $this->num3;
		$tmp4 = $this->num4;
		if($num >= 48)
		{
			$num -= 48;
			$tmp1 = $tmp4; $tmp2 = $tmp3 = $tmp4 = 0;
			$tmp1 = $tmp1 << $num;
		}
		else if($num >= 32)
		{
			$num -= 32;
			$tmp1 = $tmp3; $tmp2 = $tmp4; $tmp3 = $tmp4 = 0;
			$tmp1 = $tmp1 << $num; $tmp2 = $tmp2 << $num;
		}
		else if($num >= 16)
		{
			$num -= 16;
			$tmp1 = $tmp2; $tmp2 = $tmp3; $tmp3 = $tmp4; $tmp4 = 0;
			$tmp1 = $tmp1 << $num; $tmp2 = $tmp2 << $num; $tmp3 = $tmp3 << $num;
		}
		else
		{
			$tmp1 = $tmp1 << $num; $tmp2 = $tmp2 << $num; $tmp3 = $tmp3 << $num; $tmp4 = $tmp4 << $num;
		}
		if($num == 16 || $num == 32 || $num == 48)
		{
			$this->num1 = $tmp1; $this->num2 = $tmp2; $this->num3 = $tmp3; $this->num4 = $tmp4;
		}
		else
		{
			//$tmp1 = $tmp1 << $num; $tmp2 = $tmp2 << $num; $tmp3 = $tmp3 << $num; $tmp4 = $tmp4 << $num;
			$this->num4 = $tmp4 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
			$this->num3 = $tmp3 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
			$this->num3 |= ($tmp4 >> 16);	// apply overflow from previous group to this group
			$this->num2 = $tmp2 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
			$this->num2 |= ($tmp3 >> 16);	// apply overflow from previous group to this group
			$this->num1 = $tmp1 & 0xFFFF;	// get the last 16 bits (ignoring the overflow)
			$this->num1 |= ($tmp2 >> 16);	// apply overflow from previous group to this group
		}
		
		return $this;
	}
	// Performs the left shift and returns a new BitBoard object. Does not alter this bitboard object.
	public function shift_l_($num)
	{
		$bb = new BitBoard();
		$bb->num1 = $this->num1;
		$bb->num2 = $this->num2;
		$bb->num3 = $this->num3;
		$bb->num4 = $this->num4;
		$bb->shift_l($num);
		return $bb;
	}
	
	public function _AND($bb)
	{
		//if(!is_object($bb)) {echo "<pre>"; debug_print_backtrace(); "</pre>";}
		//BitBoard::$calls++;
		$this->num1 &= $bb->num1;
		$this->num2 &= $bb->num2;
		$this->num3 &= $bb->num3;
		$this->num4 &= $bb->num4;
		return $this;
	}
	
	public function _OR($bb)
	{
		//BitBoard::$calls++;
		$this->num1 |= $bb->num1;
		$this->num2 |= $bb->num2;
		$this->num3 |= $bb->num3;
		$this->num4 |= $bb->num4;
		return $this;
	}
	
	public function _XOR($bb)
	{
		//BitBoard::$calls++;
		$this->num1 ^= $bb->num1;
		$this->num2 ^= $bb->num2;
		$this->num3 ^= $bb->num3;
		$this->num4 ^= $bb->num4;
		return $this;
	}
	
	
	public function set_value($hex)
	{
		$hex = str_pad($hex, 16, '0', STR_PAD_LEFT);
		$this->num1 = (int)base_convert(substr($hex, 0, 4), 16, 10);
		$this->num2 = (int)base_convert(substr($hex, 4, 4), 16, 10);
		$this->num3 = (int)base_convert(substr($hex, 8, 4), 16, 10);
		$this->num4 = (int)base_convert(substr($hex, 12, 4), 16, 10);
	}
	
	public function set_to_zero()
	{
		$this->num1 = $this->num2 = $this->num3 = $this->num4 = 0;
	}
	
	// ANDs two bitboards and return a new bitboard instance with the result.
	public static function _AND_($bb1, $bb2)
	{
		return $bb1->duplicate()->_AND($bb2);
	}
	// ORs two bitboards and return a new bitboard instance with the result.
	public static function _OR_($bb1, $bb2)
	{
		return $bb1->duplicate()->_OR($bb2);
	}
	// XORs two bitboards and return a new bitboard instance with the result.
	public static function _XOR_($bb1, $bb2)
	{
		return $bb1->duplicate()->_XOR($bb2);
	}
	
	public function is_equal($bb)
	{
		return $this->num1 == $bb->num1 && $this->num2 == $bb->num2 && $this->num3 == $bb->num3 && $this->num4 == $bb->num4;
	}
	
	public function is_zero()
	{
		// It should be faster to do an assign, 3 bitwise ORs and a compare instead of 4 compares and 3 logical ANDs.
		$tmp = $this->num1 | $this->num2 | $this->num3 | $this->num4;
		return $tmp == 0;
		//return $this->num1 == 0 && $this->num2 == 0 && $this->num3 == 0 && $this->num4 == 0;
	}
	
	public function get_as_hex()
	{
		$str = str_pad(base_convert($this->num1, 10, 16), 4, '0', STR_PAD_LEFT) . str_pad(base_convert($this->num2, 10, 16), 4, '0', STR_PAD_LEFT) . str_pad(base_convert($this->num3, 10, 16), 4, '0', STR_PAD_LEFT) . str_pad(base_convert($this->num4, 10, 16), 4, '0', STR_PAD_LEFT);
		return strtoupper($str);
	}
	
	public function get_as_binary()
	{
		$str = str_pad(base_convert($this->num1, 10, 2), 16, '0', STR_PAD_LEFT) . str_pad(base_convert($this->num2, 10, 2), 16, '0', STR_PAD_LEFT) . str_pad(base_convert($this->num3, 10, 2), 16, '0', STR_PAD_LEFT) . str_pad(base_convert($this->num4, 10, 2), 16, '0', STR_PAD_LEFT);
		return $str;
	}
	
	public function duplicate()
	{
		// $new = new BitBoard();
		// $new->num1 = $this->num1;
		// $new->num2 = $this->num2;
		// $new->num3 = $this->num3;
		// $new->num4 = $this->num4;
		// return $new;
		//BitBoard::$clones++;
		BitBoard::$calls++;
		return clone $this;
	}
	
	/// <summary>
	/// Find the position of the first bit set to 1 in a 64bit number, going from the LSB to the MSB
	/// </summary>
	/// <returns>Returns the position of the first bit set to 1. If no 1bits then 63 is returned.</returns>
	public function get_pos_of_first_one_bit()
	{
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num4 & 1 << $i) != 0)
				return $i;
		}
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num3 & 1 << $i) != 0)
				return $i + 16;
		}
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num2 & 1 << $i) != 0)
				return $i + 32;
		}
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num1 & 1 << $i) != 0)
				return $i + 48;
		}
		
		return 63;
	}
	
	/// <summary>
	/// Find the position of the last 1 bit in a 64bit number, going from the MSB to the LSB.
	/// </summary>
	/// <returns>Returns the location of the last bit set to 1. If no bits are one then 0 is returned.</returns>
	public function get_pos_of_last_one_bit()
	{
		for($i = 15; $i > -1; $i--)
		{
			if(($this->num1 & 1 << $i) != 0)
				return $i + 48;
		}
		for($i = 15; $i > -1; $i--)
		{
			if(($this->num2 & 1 << $i) != 0)
				return $i + 32;
		}
		for($i = 15; $i > -1; $i--)
		{
			if(($this->num3 & 1 << $i) != 0)
				return $i + 16;
		}
		for($i = 15; $i > -1; $i--)
		{
			if(($this->num4 & 1 << $i) != 0)
				return $i;
		}
		
		return 0;
	}
	
	/// <summary>
	/// For each 1 bit found converts that to a tile number and then returns all tile numbers found.
	/// </summary>
	/// <returns>Returns an array of tile numbers.</returns>
	public function get_pos_of_all_one_bits()
	{
		$positions = array();
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num4 & 1 << $i) != 0)
				$positions[] = $i;
		}
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num3 & 1 << $i) != 0)
				$positions[] = $i + 16;
		}
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num2 & 1 << $i) != 0)
				$positions[] = $i + 32;
		}
		for($i = 0; $i < 16; $i++)
		{
			if(($this->num1 & 1 << $i) != 0)
				$positions[] = $i + 48;
		}
		
		return $positions;
	}
	
	
	public function print_nice()
	{
		echo '<pre>';
		$bin = $this->get_as_binary();
		for($row = 0; $row < 8; $row++)
		{
			for($col = 0; $col < 8; $col++)
			{
				echo $bin[$row * 8 + 7 - $col] . ' ';
			}
			echo "\n";
		}
		echo '</pre>';
	}
	
	public function debug()
	{
		echo '<pre>'; var_dump($this); echo '</pre>';
	}
	
}



/// <summary>
/// Stores bitboards needed to represent the game's state for a single move.
/// </summary>
class BitBoards
{
	public $n64WKing;
	public $n64WQueens;
	public $n64WRooks;
	public $n64WBishops;
	public $n64WKnights;
	public $n64WPawns;
	public $n64BKing;
	public $n64BQueens;
	public $n64BRooks;
	public $n64BBishops;
	public $n64BKnights;
	public $n64BPawns;
	public $n64WCastling;
	public $n64BCastling;
	public $n64EnPassant;
}

class MoveInfo
{
	/// <summary>
	/// Move represented as Standard Algebraic Notation
	/// </summary>
	public $szSAN;                           // Move represented as Standard Algebraic Notation
	/// <summary>
	/// Move represented as Long Algebraic Notation
	/// </summary>
	public $szLong;                          // Move represented as Long Algebraic Notation
	/// <summary>
	/// Start tile number
	/// </summary>
	public $nStartTile;                         // Start tile number
	/// <summary>
	/// End tile number
	/// </summary>
	public $nEndTile;                           // End tile number
	/// <summary>
	/// En passante tile available for the next move.
	/// </summary>
	public $nEnPassantTile;                     // En passante tile available for the next move.
	/// <summary>
	/// Indicates if white can castle king side.
	/// </summary>
	public $fWCastleKSide;                     // Indicates which sides each player can still castle
	/// <summary>
	/// Indicates if white can castle queen side.
	/// </summary>
	public $fWCastleQSide;                     // on following this move.
	/// <summary>
	/// Indicates if black can castle king side.
	/// </summary>
	public $fBCastleKSide;
	/// <summary>
	/// Indicates if black can castle queen side.
	/// </summary>
	public $fBCastleQSide;
	/// <summary>
	/// Represents what type of move this was.
	/// </summary>
	public $moveType;               // Represent what type of move this was.
	/// <summary>
	/// Indicates the type of promotion if this move was a promotion.
	/// </summary>
	public $promotedToType;   	 	// Indicates the type of promotion if this move was a promotion
	/// <summary>
	/// The side to which this move belongs.
	/// </summary>
	public $nSideMoved;            // The side to which this move belongs to.
	/// <summary>
	/// Status of the board at this move.
	/// </summary>
	public $boardStatus;      // Status of the board at this move.
	/// <summary>
	/// Comment for annotations.
	/// </summary>
	public $szComment;                       // Comment for annotations???
	/// <summary>
	/// Bit boards for this move.
	/// </summary>
	public $Boards;                       // Bitboards for this move.
	/// <summary>
	/// Number of half moves since the last pawn move.
	/// </summary>
	public $nHalfMoveClock;                     // Number of half moves since the last pawn move.
	
	public $takenPieceType;			// If a piece was taken, stores the type of the piece.
	/// <summary>
	/// List of move variations.
	/// </summary>
	//public $Variations = array();              // List of variations.
	/// <summary>
	/// The index into the variation used.
	/// </summary>
	//public $nVariationUsed;                     // The index into the variation used. -1 means no variation used.
}

class ChessMoveList
{
	/// <summary>
	/// Stores the starting state of the game and references to all moves made from there on.
	/// </summary>
	private $StartState;             // Stores the starting state of the game and references to all moves made from there on.                     
	/// <summary>
	/// Side whose turn it currently is.
	/// </summary>
	private $nCurSide;                     // Side whose turn it currently is.
	/// <summary>
	/// Move ply number for the starting move.
	/// </summary>
	private $nStartPly;                    // Move ply number for the starting move.
	/// <summary>
	/// The side which started.
	/// </summary>
	private $nSideStarted;                 // The side which started.

	private $move_list;					// List of moves made.
	
	/// <summary>
	/// Initialises move list by supplying the starting state of the game.
	/// </summary>
	/// <param name="nStartPly">The move ply to start from when loading a FEN game. Else it can be just 1.</param>
	public function __construct($nSideStarted, $fWCastleK, $fWCastleQ, 
		$fBCastleK, $fBCastleQ, $nEnPassant, $nHalfMoves,
		$boardStatus, $Boards)
	{
		$this->StartState = new MoveInfo();
		$this->move_list = array();
		
		$this->nCurSide = $this->nSideStarted;
		$this->nSideStarted = $this->nCurSide == 0 ? 1 : 0;		// ?? is this right?
		$this->nStartPly = 0;// nStartingStateMove;

		if ($this->nSideStarted == 0)
			$this->StartState->nSideMoved = PLAYER_SIDE::WHITE;
		else
			$this->StartState->nSideMoved = PLAYER_SIDE::BLACK;
		$this->StartState->nEnPassantTile = $nEnPassant;
		$this->StartState->fWCastleKSide = $fWCastleK;
		$this->StartState->fWCastleQSide = $fWCastleQ;
		$this->StartState->fBCastleKSide = $fBCastleK;
		$this->StartState->fBCastleQSide = $fBCastleQ;
		$this->StartState->nHalfMoveClock = $nHalfMoves;
		$this->StartState->boardStatus = $boardStatus;
		$this->StartState->Boards = $Boards;
		//$this->StartState->nVariationUsed = -1;
	}

	/// <summary>
	/// Adds a move to the currently used move list.
	/// </summary>
	/// <param name="nStartTile">The start tile as integer.</param>
	/// <param name="nEndTile">The end tile as integer.</param>
	/// <param name="moveType">The type of move made.</param>
	/// <param name="takenPieceType">If move resulted in a capture, indicates the type of the captured piece.</param>
	/// <param name="promotedTo">The promotion type (if promotion occurred).</param>
	/// <param name="szSAN">The SAN representation of this move.</param>
	/// <param name="szLongNotation">The long algebraic notation of this move.</param>
	public function RecordMove($nStartTile, $nEndTile, $moveType, $takenPieceType, $promotedTo, $fWCastleK, $fWCastleQ, 
		$fBCastleK, $fBCastleQ, $nEnPassant, $nHalfMoves, $szSAN, $szLongNotation, $boardStatus, 
		$szComment, $Boards)
	{
		$szOutput = "";
		$move = new MoveInfo();
		$lastMove;
		$nMoveNum = 0;

		$move->moveType = $moveType;
		$move->nEndTile = $nEndTile;
		$move->nStartTile = $nStartTile;
		$move->nSideMoved = $this->nCurSide == 0 ? PLAYER_SIDE::WHITE : PLAYER_SIDE::BLACK;
		$move->nEnPassantTile = $nEnPassant;
		$move->fWCastleKSide = $fWCastleK;
		$move->fWCastleQSide = $fWCastleQ;
		$move->fBCastleKSide = $fBCastleK;
		$move->fBCastleQSide = $fBCastleQ;
		$move->takenPieceType = $takenPieceType;
		$move->promotedToType = $promotedTo;
		$move->nHalfMoveClock = $nHalfMoves;
		$move->szLong = $szLongNotation;
		$move->szSAN = $szSAN;
		$move->boardStatus = $boardStatus;
		$move->szComment = $szComment;
		$move->Boards = $Boards;

		// Get the last moveInfo variable
		//$nMoveNum = GetLastMoveInfoVar($lastMove);
		//// If there is no variations list then need to create one.
		//if (lastMove.Variations == null) lastMove.Variations = new List<MoveInfo>();
		//lastMove.Variations.Add(move);
		//lastMove.nVariationUsed = lastMove.Variations.Count - 1;

		$this->move_list[] = $move;
		
		$this->nCurSide = ($this->nCurSide == 1) ? 0 : 1;

		$nMoveNum += $this->nStartPly;
		$szOutput = "MoveInfo " . str_pad($nMoveNum, 3, ' ', STR_PAD_LEFT);
		$szOutput .= " SIDE: " . $move->nSideMoved . " START: " . str_pad($move->nStartTile, 2, ' ', STR_PAD_LEFT);
		$szOutput .= " END: " . str_pad($move->nEndTile, 2, ' ', STR_PAD_LEFT);
		$szOutput .= " MOVETYPE: " . str_pad($move->moveType, 9, ' ', STR_PAD_RIGHT);
		$szOutput .= " BOARDSTATE: " . str_pad($move->boardStatus, 9, ' ', STR_PAD_RIGHT);
		$szOutput .= " SAN: " . str_pad($move->szSAN, 7, ' ', STR_PAD_RIGHT);
		$szOutput .= " LAN: " . str_pad($move->szLong, 7, ' ', STR_PAD_RIGHT);
		$szOutput .= " ENPASSANT TILE: " . str_pad($move->nEnPassantTile, 2, ' ', STR_PAD_LEFT);
		$szOutput .= " PROMOTION: " . str_pad($move->promotedToType, 9, ' ', STR_PAD_RIGHT);
		$szOutput .= " HALFMOVECLOCK: " . str_pad($move->nHalfMoveClock, 3, ' ', STR_PAD_LEFT);
		$szOutput .= " CASTELING(KQkq): " . $move->fWCastleKSide . " " . $move->fWCastleQSide . " " . $move->fBCastleKSide . " " . $move->fBCastleQSide;
		//LogFile.Write(szOutput);
		//LogFile.FlushToFile();
	}

	/// <summary>
	/// Used to go through the move list for the current variation and returns the last move.
	/// </summary>
	/// <param name="move">Returns a reference to the moveInfo object of the last move.</param>
	/// <returns>An integer representing the number of moves in the variation.</returns>
	private function GetLastMoveInfoVar(&$move)
	{
		// int nCount = 0;

		// //// See if there is a move at all.
		// //if(m_nStartState.Variations == null)
		// //{
		// //    m_nStartState.Variations = new List<MoveInfo>();
		// //    //m_nStartState.Variations.Add(new MoveInfo());
		// //    move = m_nStartState;
		// //    return 0;
		// //}

		// //move = m_nStartState.Variations[m_nStartState.nVariationUsed];
		
		// //while (fLoop)
		// move = $this->StartState;
		// while (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
		// {
			// nCount++;
			// //if (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
			// //{
				// move = move.Variations[move.nVariationUsed];
			// //}
			// //else
			// //{
			// //    //move.Variations = new List<MoveInfo>();
			// //    fLoop = false;
			// //}
		// }
		// return nCount;
	}

	/// NOTE: games can be setup with a given ply number to start from. Ply numbers begin at 1.
	///	the array storing the move lists is 0 indexed. In the future subtract the starting ply number
	/// from the ply number supplied to the function. Will need to validate all code that uses this 
	/// function and if need be make changes. This will become necessary if variations are ever to
	/// be implemented.
	/// <summary>
	/// Gets the moveInfo object with the given plyMove number in the current move list.
	/// </summary>
	/// <param name="nMoveIndex">The move index.</param>
	/// <returns>Returns the moveInfo object.</returns>
	public function GetMove($nMoveIndex)
	{
		$nCount = 0;
		//$move = $this->StartState;
		if ($nMoveIndex == $this->nStartPly) 	// This works because start ply is currently set to 0.
			return $this->StartState;
		return $this->move_list[$nMoveIndex - 1];
			
		// while (move.Variations != null && move.Variations.Count != 0)
		// {
			// nCount++;
			// move = move.Variations[move.nVariationUsed];
			// if (nCount == $nMoveIndex)
			// {
				// return move;
			// }
		// }
		//return null;
	}

	/// <summary>
	/// Returns the list of moves for the current movelist variation.
	/// </summary>
	/// <returns>A list of move objects representing the moves for the current variation</returns>
	public function GetMoveList()
	{
		return $this->move_list;
		// List<MoveInfo> moveList = new List<MoveInfo>();
		// MoveInfo move = $this->StartState;
		// while (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
		// {
			// move = move.Variations[move.nVariationUsed];
			// moveList.Add(move);
		// }
		// return moveList;
	}

	/// <summary>
	/// Returns how many variations there are for a given ply move in the current game variation.
	/// </summary>
	/// <param name="nPlyMove"></param>
	/// <returns></returns>
	public function GetVariationCountForMove($nPlyMove)
	{
		//MoveInfo move = GetMove(nPlyMove);
		//if (move != null) return move.Variations.Count;
		return 0;
	}

	/// <summary>
	/// The number of moves in the current variation move list.
	/// </summary>
	public function MoveCount()
	{
		return count($this->move_list);
		// get 
		// {
			// int nCount = 0;
			// MoveInfo move = $this->StartState;

			// while (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
			// {
				// nCount++;
				// move = move.Variations[move.nVariationUsed];
			// }
			// return nCount;
		// }
	}

	/// <summary>
	/// Returns the ply number for the starting move in this list.
	/// </summary>
	/// <returns>Returns the ply number for the starting move in this list.</returns>
	public function GetStartingMovePlyNumber()
	{
		return $this->nStartPly;
	}

	/// <summary>
	/// Returns which side started moving.
	/// </summary>
	/// <returns>White or Black</returns>
	public function GetStartingSide()
	{
		return $this->nSideStarted == 0 ? PLAYER_SIDE::WHITE : PLAYER_SIDE::BLACK;
	}

	///// <summary>
	///// Removes the last ply move in the current move variation and returns the new last move.
	///// If all moves in a veriation have been undone the next variation is then made active.
	///// </summary>
	///// <returns>Returns true if the last move could be removed.</returns>
	//public bool RemoveLastMove(ref MoveInfo newLastMove)
	//{
	//    MoveInfo move = m_nStartState;
	//    // Find the last move through the current variation.
	//    while (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
	//    {
	//        newLastMove = move;
	//        move = move.Variations[move.nVariationUsed];
	//    }
	//    // Make sure this isn't the initial state of the game.
	//    if (move == m_nStartState) return false;
	//    // Remove the move from the list.
	//    newLastMove.Variations.Remove(move);
	//    // If the move undone was part of a variation of moves for this ply move number, then
	//    // a new variation from the list needs to be made active.
	//    if (newLastMove.Variations.Count > 1)
	//    {
	//        newLastMove.nVariationUsed = 0;
	//        // Need to find the last move in this variation.
	//        move = newLastMove;
	//        while (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
	//        {
	//            //newLastMove = move;
	//            move = move.Variations[move.nVariationUsed];
	//        }
	//        newLastMove = move;
	//        m_nCurSide = newLastMove.nSideMoved == 1 ? 0 : 1;
	//        return true;
	//    }

	//    m_nCurSide = (m_nCurSide == 1) ? 0 : 1;

	//    return true;
	//}

	/// <summary>
	/// Removes the last ply move in the current move variation and returns the new last move.
	/// </summary>
	/// <returns>Returns true if the last move could be removed.</returns>
	public function RemoveLastMove(&$newLastMove)
	{
		array_pop($this->move_list);
		if(count($this->move_list) == 0)
		{
			$newLastMove = $this->StartState;
			return false;
		}
		$newLastMove = $this->move_list[count($this->move_list) - 1];
		return true;
		// MoveInfo move = $this->StartState;
		// // Find the last move through the current variation.
		// while (move.Variations != null && move.nVariationUsed > -1 && move.nVariationUsed < move.Variations.Count)
		// {
			// newLastMove = move;
			// move = move.Variations[move.nVariationUsed];
		// }
		// // Make sure this isn't the initial state of the game.
		// if (move == $this->StartState) return false;
		// // Remove the move from the list.
		// newLastMove.Variations.Remove(move);

		// $this->nCurSide = ($this->nCurSide == 1) ? 0 : 1;

		// return true;
	}

	/// <summary>
	/// Checks if the current board position (with en passant/castle states) has been repeated 2 other times
	/// for the current player.
	/// </summary>
	/// <returns>True if 3 fold repetition has occurred; otherwise false.</returns>
	public function CheckCurrentMoveFor3FoldRepetition()
	{
		$nOccurances = 1;
		$CurMoveInfo = $this->GetMove($this->MoveCount());

		// Starting from the 3rd last move, check if the board position and en passant castling
		// state are the same for two other moves with the same player having the move.
		for ($i = $this->MoveCount() - 3; $i > -1; $i -= 2)
		{
			$CompareTo = $this->GetMove($i + 1);
			if ($CompareTo->Boards->n64BBishops->is_equal($CurMoveInfo->Boards->n64BBishops) &&
				$CompareTo->Boards->n64BCastling->is_equal($CurMoveInfo->Boards->n64BCastling) &&
				$CompareTo->Boards->n64BKing->is_equal($CurMoveInfo->Boards->n64BKing) &&
				$CompareTo->Boards->n64BKnights->is_equal($CurMoveInfo->Boards->n64BKnights) &&
				$CompareTo->Boards->n64BPawns->is_equal($CurMoveInfo->Boards->n64BPawns) &&
				$CompareTo->Boards->n64BQueens->is_equal($CurMoveInfo->Boards->n64BQueens) &&
				$CompareTo->Boards->n64BRooks->is_equal($CurMoveInfo->Boards->n64BRooks) &&
				$CompareTo->Boards->n64EnPassant->is_equal($CurMoveInfo->Boards->n64EnPassant) &&
				$CompareTo->Boards->n64WBishops->is_equal($CurMoveInfo->Boards->n64WBishops) &&
				$CompareTo->Boards->n64WCastling->is_equal($CurMoveInfo->Boards->n64WCastling) &&
				$CompareTo->Boards->n64WKing->is_equal($CurMoveInfo->Boards->n64WKing) &&
				$CompareTo->Boards->n64WKnights->is_equal($CurMoveInfo->Boards->n64WKnights) &&
				$CompareTo->Boards->n64WPawns->is_equal($CurMoveInfo->Boards->n64WPawns) &&
				$CompareTo->Boards->n64WQueens->is_equal($CurMoveInfo->Boards->n64WQueens) &&
				$CompareTo->Boards->n64WRooks->is_equal($CurMoveInfo->Boards->n64WRooks))
			{
				$nOccurances++;
				if ($nOccurances == 3)
					return true;
			}
		}
		// Check the starting state to see if it was repeated, as the starting state isn't included
		// in the move list.
		if ($CurMoveInfo->Boards->n64BBishops->is_equal($this->StartState->Boards.n64BBishops) &&
			$CurMoveInfo->Boards->n64BCastling->is_equal($this->StartState->Boards.n64BCastling) &&
			$CurMoveInfo->Boards->n64BKing->is_equal($this->StartState->Boards.n64BKing) &&
			$CurMoveInfo->Boards->n64BKnights->is_equal($this->StartState->Boards.n64BKnights) &&
			$CurMoveInfo->Boards->n64BPawns->is_equal($this->StartState->Boards.n64BPawns) &&
			$CurMoveInfo->Boards->n64BQueens->is_equal($this->StartState->Boards.n64BQueens) &&
			$CurMoveInfo->Boards->n64BRooks->is_equal($this->StartState->Boards.n64BRooks) &&
			$CurMoveInfo->Boards->n64EnPassant->is_equal($this->StartState->Boards.n64EnPassant) &&
			$CurMoveInfo->Boards->n64WBishops->is_equal($this->StartState->Boards.n64WBishops) &&
			$CurMoveInfo->Boards->n64WCastling->is_equal($this->StartState->Boards.n64WCastling) &&
			$CurMoveInfo->Boards->n64WKing->is_equal($this->StartState->Boards.n64WKing) &&
			$CurMoveInfo->Boards->n64WKnights->is_equal($this->StartState->Boards.n64WKnights) &&
			$CurMoveInfo->Boards->n64WPawns->is_equal($this->StartState->Boards.n64WPawns) &&
			$CurMoveInfo->Boards->n64WQueens->is_equal($this->StartState->Boards.n64WQueens) &&
			$CurMoveInfo->Boards->n64WRooks->is_equal($this->StartState->Boards.n64WRooks))
		{
			$nOccurances++;
			if ($nOccurances == 3)
				return true;
		}
		return false;
	}

}

?>