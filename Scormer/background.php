<?php
#$imb = imageCreateFromPng(dirname(__FILE__)."/paper-background2.png");
$im = imageCreate(10000, 10000);

/*
for($y=0;$y<10000;$y+=imageSy($imb)) {
	for($x=0;$x<10000;$x+=imageSx($imb)) {
		imageCopy($im, $imb, $x,$y,0,0,imageSy($imb),imageSx($imb));
	}
}
*/

$bg = imageColorAllocate($im, 255,255,255);
$fg = imageColorAllocate($im, 200,200, 200);
$line = imageColorAllocate($im, 50,50,50);
imageFilledRectangle($im, 0,0,10000,10000,$bg);

for($y=0;$y<10000;$y+=50) {
	imageLine($im,0,$y,10000,$y,$fg);
}
for($x=0;$x<10000;$x+=50) {
	imageLine($im,$x,0,$x,10000,$fg);
}

$E = explode(",", $_GET["edges"]);
$A = explode(",", $_GET["arrows"]);
$j=0;
for($i=0;$i<count($E);$i+=4) {
	imageLine($im, $E[$i], $E[$i+1], $E[$i+2], $E[$i+3], $line);
	$dx = $E[$i]-$E[$i+2];
	$dy = $E[$i+1]-$E[$i+3];
	$len = sqrt($dx*$dx+$dy*$dy)/5;

	$dx2 = $E[$i+2]-$E[$i];
	$dy2 = $E[$i+3]-$E[$i+1];

	/*
	imageLine($im, $E[$i+2], $E[$i+3], $E[$i+2]-$dy/$len+$dx/$len*2, $E[$i+3]+$dx/$len+$dy/$len*2,$line);
	imageLine($im, $E[$i+2], $E[$i+3], $E[$i+2]+$dy/$len+$dx/$len*2, $E[$i+3]-$dx/$len+$dy/$len*2,$line);
	*/
	
	if($A[$j]=="ende" || $A[$j]=="beide") {
		// Spitze am Ende
		$points = array($E[$i+2], $E[$i+3],$E[$i+2]-$dy/$len+$dx/$len*2, $E[$i+3]+$dx/$len+$dy/$len*2, $E[$i+2]+$dy/$len+$dx/$len*2, $E[$i+3]-$dx/$len+$dy/$len*2);
		imagefilledpolygon($im, $points, count($points)/2, $line);
	}

	if($A[$j]=="start" || $A[$j]=="beide") {
		// Spitze am Anfang
		$points = array($E[$i+0], $E[$i+1],$E[$i+0]-$dy2/$len+$dx2/$len*2, $E[$i+1]+$dx2/$len+$dy2/$len*2, $E[$i+0]+$dy2/$len+$dx2/$len*2, $E[$i+1]-$dx2/$len+$dy2/$len*2);
		imagefilledpolygon($im, $points, count($points)/2, $line);
	}
	
	$j++;
}



imagePng($im);
