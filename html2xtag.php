<?php
  //
  // Copyright (c) 2008 Lifestyle Media Group. All Rights Reserved
  //
  // Take newsletter id as a parameter
  // select all front page articles and output
  // select all back pages articles (or billboard) and output
  // select all 31 calendar entries and output
  // select staff/hours/contacts and output
  // select address bar and output
if (isset($_POST['body'])) {
$body = $_POST['body'];
$new_article = html2xtag($body);
echo $new_article;
// return $new_article;
}


function convertVTtoEOL($str)
{
  $match = array("\xb");
  $replace = array("\xd");
  $outstr=str_replace($match,$replace,$str);
  return(mysql_real_escape_string($outstr));
}

function newlinecleaner($str)
{
  $match = array("\n","\r");
  $replace = array("\xb","\xb");
  $outstr=str_replace($match,$replace,$str);
  return($outstr);
}

function ampcleaner($str)
{
  $match = array("@");
  $replace = array("<\@>");
  $outstr=str_replace($match,$replace,$str);
  return(newlinecleaner($outstr));
}

function tabcleaner($str)
{
  $match = array("\t");
  $replace = array(" ");
  $outstr=str_replace($match,$replace,$str);
  return($outstr);
}

function cleaner2($str)
{
  $match = array("/","&ndash;","&mdash;","&lsquo;","&rsquo;","&ldquo;","&rdquo;","<br>&bull;","<br />&bull;","&hellip;","<br>");
  $replace = array("\xe2\x81\x84","\xe2\x80\x93","\xe2\x80\x94","\xe2\x80\x98","\xe2\x80\x99","\xe2\x80\x9c","\xe2\x80\x9d","\xe2\x80\xa2","\xe2\x80\xa2","\xe2\x80\xa6","\xb");
  $outstr=str_replace($match,$replace,$str);
  return($outstr);
}

function cleaner($str)
{
  //tbd hellip,bull
  // bull <\#U2022>
  // hellip <\#U2026>
  //
  // Page 24 of the Xtag Users Guide, search for 'Insert Unicode special'
  // Lookup unicode/html entities via http://www.eki.ee/letter/
  //
  $match = array("&ndash;","&mdash;","&lsquo;","&rsquo;","&ldquo;","&rdquo;",
		 "&bull;","&hellip;","&nbsp;");
  $replace = array("<\#208>","<\#209>","<\#212>","<\#213>","<\#210>","<\#211>",
		   "<\#U2022>","<\#U2026>"," ");
  $outstr=str_replace($match,$replace,$str);
  return($outstr);
}


// streamedit span's into xtags. NOT handling nested spans right now
//
function streamedit()
{
  global $bodystr;
  global $editedstr;
  $s1=strpos($bodystr,"<span");
  if ($s1!==FALSE)
    {
      if ($s1>0)
	{
	  $editedstr.=substr($bodystr,0,$s1);
	}
      $tmpscan=substr($bodystr,$s1);
      $endpos=strpos($tmpscan,">")+1;
      $tmpscan1=substr($tmpscan,0,$endpos);
      if (strpos($tmpscan1,"\">")===false)
	$s2=strpos($tmpscan1,">")+$s1;
      else
	$s2=strpos($tmpscan1,"\">")+1+$s1;
      if ($s2!==FALSE)
	{
	  $spanstr=substr($bodystr,$s1,$s2-$s1+1);
	  $red=strpos($spanstr,"ff0000") || strpos($spanstr,"FF0000");
	  $black=strpos($spanstr,"000000");
	  $bold=strpos($spanstr,"bold");
	  $italic=strpos($spanstr,"italic");
	  $s3=strpos($bodystr,"</span>");
	  $textstr=substr($bodystr,$s2+1,$s3-$s2-1);
	  $scheck=strpos($textstr,"<span");
	  if ($scheck!==FALSE)
	    {
	      $tmpstr=substr($bodystr,$s3+7);
	      $s3+=strpos($tmpstr,"</span>")+7;
	      $textstr=substr($bodystr,$s2+1,$s3-$s2-1);
	    }
	  if ($red) $editedstr.="<c\"Red\">";
	  //else if ($black) echo "<cK>";
	  if ($bold) $editedstr.="<B>";
	  if ($italic) $editedstr.="<I>";
	  $editedstr.=$textstr;
	  if ($italic) $editedstr.="<I>";
	  if ($bold) $editedstr.="<B>";
	  if ($red) $editedstr.="<cK>";
	  //else if ($black) echo "<cK>";
	  $bodystr=substr($bodystr,$s3+7);
	}
      else
	{
	  $editedstr.=$bodystr;
	  $bodystr="";
	}
    }
  else
    {
      $editedstr.=$bodystr;
      $bodystr="";
    }
}


// html2xtag
// Take a single article (headline/body pair) and convert it to XTAG'ed text
//
function html2xtag($body)
{
  global $bodystr;
  global $editedstr;

  // Retrieve the stylesheet name from the configuration value
  $bodyx=preg_replace('/\<p ([A-Za-z0-9\@\.\ \,\_\:\#\-\;\%\"\=]+)>/','<p>',$body);
  $body2=preg_replace('/\<p class=\"(\w+)\">/','<p>',$bodyx);
  $body2a=preg_replace('/\<a ([\/A-Za-z0-9\@\.\ \,\_\:\#\-\;\%\"\=]+)>/','',$body2);
  $body2b=preg_replace('/\<div ([\/A-Za-z0-9\@\.\ \,\_\:\#\-\;\%\"\=]+)>/','',$body2a);
  $body2c=preg_replace('/\<!([\/A-Za-z0-9\@\.\ \,\_\:\#\-\;\%\"\=]+)>/','',$body2b);
  $body2d=preg_replace('/\<\!\-\-\[if(.*)endif\]\-\-\>/eis','',$body2c);
  $body2e=preg_replace('/\<\!\-\-\ (.*)\-\-\>/eis','',$body2d);

  // Main conversion of html entities to XTAGS
  $match = array("font-size: 14px; ","font-family: 'Arial'; ","<strong>","</strong>","<em>","</em>","<u>","</u>",
		 "<font color=\"red\">","<font color=\"#ff0000\">","</font>","SPAN","<BR","<p>","</p>",
		 ";\" >","</a>","<div>","</div>",
		 "<ul>","</ul>","<li>","</li>","\n");
  $replace = array("","","<B>","<B>","<I>","<I>","<U>","<U>",
		   "","","<cK>","span","<br","\xb","",";\">","","","",
		   "","","\xb<\#165>","","");
  // Do initial cleanup before stream edit
  $parsed_body=str_replace($match,$replace,$body2e);
  $bodystr=$parsed_body;

  while (strcmp($bodystr,"")!=0)
    {
      streamedit();
    }
  $bodystr=$editedstr;
  $s1=strpos($bodystr,"<span");
  if ($s1!==FALSE)
    {
      $editedstr="";
      while (strcmp($bodystr,"")!=0)
	{
	  streamedit();
	}
    }
  //app_error_log("editedstr ".$editedstr);
  // cleanup htmlentities, etc.
  if (1)
    {
      // convert the hard breaks into ^k file FileMaker import
      $match = array("<br>","<br />");
      $replace = array("\xb","\xb");
      $str2=str_replace($match,$replace,$editedstr);


      $match =array("&quot;","&amp;","&ndash;","&mdash;","&lsquo;","&rsquo;","&ldquo;","&rdquo;",
		    "&bull;","&hellip;","&nbsp;","@",
		    "&deg;",
		     "&Aacute;",
		     "&aacute;",
		     "&Eacute;",
		     "&eacute;",
		     "&Iacute;",
		     "&iacute;",
		     "&Ntilde;",
		     "&ntilde;",
		     "&Oacute;",
		     "&oacute;",
		     "&Uacute;",
		     "&uacute;",
		     "&Uuml;",
		     "&uuml;",
		     "&laquo;",
		     "&raquo;",
		     "&iquest;",
		     "&iexcl;",
		     "&Ccedil;",
		     "&ccedil;",
         "&#39;"
		    );
      $replace=array("\"","&","<\#208>","<\#209>","<\#212>","<\#213>","<\#210>","<\#211>",
		     "<\#165>","<\#201>"," ","<\@>",
		     " ",
		     "<\#193>",
		     "<\#225>",
		     "<\#201>",
		     "<\#233>",
		     "<\#205>",
		     "<\#237>",
		     "<\#209>",
		     "<\#241>",
		     "<\#211>",
		     "<\#243>",
		     "<\#218>",
		     "<\#250>",
		     "<\#220>",
		     "<\#252>",
		     "<\#171>",
		     "<\#187>",
		     "<\#191>",
		     "<\#161>",
		     "<\#199>",
		     "<\#231>",
         "<\#213>"
		     );
      $outstr=str_replace($match,$replace,$str2);

      // remove whitespace, extra VT's at the end of the article
      $outstr1=rtrim($outstr);
      // same for start of article
      $outstr2=ltrim($outstr1);

      $outstr3=preg_replace('/\<font([A-Za-z0-9\ \,\:\#\-\;\%\"\=]+)>/','',$outstr2);

      // Eject the parsed stuff
      $parsed_body=$outstr3;
    }
  else
    {
      $match = array("&ndash;",
		     "&mdash;","&lsquo;","&rsquo;","&ldquo;",
		     "&rdquo;","<br>&bull;","<br />&bull;","&hellip;",
		     "<br>","<br />","&nbsp;");
      $replace = array("\xe2\x80\x93",
		       "\xe2\x80\x94","\xe2\x80\x98","\xe2\x80\x99","\xe2\x80\x9c",
		       "\xe2\x80\x9d","\xe2\x80\xa2","\xe2\x80\xa2","\xe2\x80\xa6",
		       "\xb","\xb"," ");
      // Eject the parsed stuff
      $parsed_body=str_replace($match,$replace,$editedstr);
    }
  $tmps1=newlinecleaner($parsed_body);
  $tmps2=preg_replace('/\<span ([\/A-Za-z0-9\@\/\'\.\ \,\:\#\-\;\%\"\=]+)>/','',
		      $tmps1);
  $tmps3=preg_replace('/\<h([\/A-Za-z0-9\@\/\'\.\ \,\:\#\-\;\%\"\=]+)>/','',
		      $tmps2);
  $match =array("<span>","</span>","<h4>","</h4>","</h5>"
		,"<h3>","</h3>"
		,"<h2>","</h2>"
		,"<h1>","</h1>"
		,"<sup>","</sup>"
		);
  $replace=array("","","","",""
		 ,"",""
		 ,"",""
		 ,"","");
  $tmps4=str_replace($match,$replace,$tmps3);
  return(newlinecleaner($tmps4));
}



// _getArticleFlow
// Create a complete Quark flow for a set of articles (frontpage or backpage)
//
function _getArticleFlow($nrow,$nid,$type)
{
  global $bodystr;
  global $editedstr;
  $s="";
  $sql="SELECT * FROM user_articles WHERE newsletter_id ='" . $nid . "' AND type='" . $type . "' ORDER BY position";
  app_error_log($sql,0);
  $result = mysql_query($sql);
  $num=mysql_num_rows($result);
  if ($num)
    {
      $s=$s . xtagStartArticles();
      while($row = mysql_fetch_array($result))
	{
	  $bodystr="";
	  $editedstr="";
	  $s2=html2xtag($row['headline'], $row['body']);
	  $s=$s . $s2;
	  //echo "<br>" . $row['headline'];
	  //echo "<br>" . $row['body'];
	  //echo "<br>" . $s2;
	}
    }
  else
    {
      $s=$s . "NO ARTICLES SPECIFIED";
    }
  $s="'" . convertVTtoEOL($s) . "',";
  return($s);
}


?>