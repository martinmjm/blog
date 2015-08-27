<html>
<head>
<title>Martin's blog</title>
<link rel="stylesheet" type="text/css" href="blog.css">
<link rel="stylesheet" type="text/css" href="../header.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<!--#include virtual="../header.html" -->
<div id="left">
<?php
       include('/var/www/dbsetup.php');
       $CON = mysql_connect("localhost",$MYSQLUSERNAME,$MYSQLPASSWORD);
       if (!$CON)
         {
            die('Error When Connecting To Database: ' . mysql_error());
         }
       $ERRMESG="";
       mysql_query("use blogo;",$CON);

      // now establish the EID (Entry ID)
      if(isset($_GET['EID']) && is_numeric($_GET['EID']))
      {
        $EID = $_GET['EID'];
      } 
      else
      {  // value from GET is not numeric
        if(isset($_POST['EID']) && is_numeric($_POST['EID']))
        { // value from POST is numeric
          $EID = $_POST['EID'];
          $EIDFROMPOST = 1;
        }
        else
        {
          $EID = 0; // value gotten neither from GET or POST
        }
      } // EID now set and numeric; test for blog entry:
      if ($EID!=0)
      {
        $MQ = "SELECT COUNT(1) AS CNT FROM tblEntries WHERE active=1 AND EntrID=" . $EID;
        // this is to check the EID value for an actual active blog entry
        $ROW = mysql_fetch_array(mysql_query($MQ,$CON));
        if($ROW['CNT']!=1)
        {
          // echo "Wrong EID supplied: no such entry; reverting to default";
          $EID = 0;
          // $EIDFROMPOST = 0; no need as will be set below
        }
      }
      if($EID==0)
      {
        // echo "getting the last blog entry as default"; // for $EID
        $EIDFROMPOST = 0;
        $MQ = "SELECT MAX(EntrID) AS MaxEID FROM tblEntries WHERE active=1";
        //echo "now EID is " . $EID;
        if(!($RESULT = mysql_query($MQ,$CON)))
        {
          echo "Max EID Query Failed";
        }
        if(!($AEID = mysql_fetch_array($RESULT)))
        {
          echo "Max EID Query Did Not Return A Value";
        }
        $EID = $AEID['MaxEID'];
        //$EID = 99;
        if (isset($_GET['EID'])) 
        {
          echo "<p class=\"mok\">just don't :P :P :P</p>";
        }
      }
      // EID (Entry ID) established :))


      if(isset($_POST['CTEXT'])) 
      { $CURTEXT=$_POST['CTEXT']; } 
      else 
      { $CURTEXT=""; }
      if(isset($_POST['APA']) && isset($_POST['BPA']) 
          && ($CURTEXT!="") && ($EIDFROMPOST==1))
      { // if we were invoked by some kind of comment submission
        if (isset($_POST['CPA']) && ($_POST['APA']-$_POST['BPA']==$_POST['CPA']))
        { // antispam is valid :)
          if (isset($_POST['CNAME'])&&($_POST['CNAME']!=""))
          { $SNAME = $_POST['CNAME']; }
          else
          { $SNAME = "Anonymous"; }

          // sanitising $SNAME and $CURTEXT
          $CURTEXT = str_replace("\\","\\\\",$CURTEXT);
          $CURTEXT = str_replace("'","\\'",$CURTEXT);
          $CURTEXT = str_replace("<","&lt;",$CURTEXT);
          $CURTEXT = str_replace(">","&gt;",$CURTEXT);
          $CURTEXT = str_replace(array("\r\n","\r","\n"),"<br/>",$CURTEXT);
 
          $SNAME = str_replace("\\","\\\\",$SNAME);
          $SNAME = str_replace("'","\\'",$SNAME);
          $SNAME = str_replace("<","&lt;",$SNAME);
          $SNAME = str_replace(">","&gt;",$SNAME);
          $SNAME = str_replace(array("\r\n","\r","\n"),"",$SNAME);

          $MQ = "INSERT INTO tblComm SET CommID=0, CommTime=NOW(), EID=" . $EID 
                . ",CommName='" . $SNAME . "',CommText='" . $CURTEXT 
                . "',active=1";
          if(!mysql_query($MQ,$CON)) 
          {
            $ERRMESG = "Database write failed";
            // $ERRMESG = $ERRMESG . ": " . $MQ;
          }
          else
          { // comment was sumbitted :)
            $CURTEXT = "";
          }
        }
        else
        { $ERRMESG="Antispam incorrect; please try again.";}
      }
      // done with submission; now table of contents
      unset($RESULT);
      $RESULT = mysql_query("SELECT EntrID,EntrTime,Title,lang FROM tblEntries WHERE active=1 ORDER BY EntrTime DESC",$CON);

      echo "<ul>";
      while ($ROW = mysql_fetch_array($RESULT))
      {
        echo "<li><a href=\"index.php?EID=" . $ROW['EntrID'] . "\">";
        echo $ROW['EntrTime'] . ": " . $ROW['Title'] ;
        echo "</a></li>";

      }
      echo "</ul>";

?>
<hr>
  <div id="newcoms">
  <h4>Most Recent Comments</h4>
  <?php
     $SNIPLENGTH = 70;
     $MQ = "SELECT TC.CommID, TC.EID, TC.CommTime, TC.CommName, TE.Title, " .
             " LEFT(REPLACE(TC.CommText,'<br/>','/'),".$SNIPLENGTH.") AS CommStart," .
             " CHAR_LENGTH(REPLACE(TC.CommText,'<br/>','/')) AS CommLength " .
           " FROM tblComm AS TC LEFT JOIN tblEntries AS TE ON TC.EID=TE.EntrID " . 
           " WHERE TC.active=1 AND TE.active=1 ORDER BY TC.CommID DESC LIMIT 3";
     $RESULT = mysql_query($MQ,$CON);
     while ($ROW = mysql_fetch_array($RESULT))
     {
       echo "<div class=\"newcom\">";
       echo "<p class=\"ncomtime\"><a href=\"index.php?EID=" . $ROW['EID'] 
         . "#cid" . $ROW['CommID'] . "\">" 
         . $ROW['CommTime'] . "</a>&nbsp;&nbsp;&nbsp;</p>";
       echo "<p class=\"ncomname\">" . $ROW['CommName'] . "</p>";
       echo "<p class=\"ncomtitle\">" . $ROW['Title'] . "</p>";
       echo "<p class=\"ncomstart\">" . $ROW['CommStart'] .
           ($ROW['CommLength'] > $SNIPLENGTH ? "..." : "") . "</p>";
       echo "</div>";
       echo "<hr/>";
       //$I = $I + 1;
     }
  ?>
  </div> <!-- end of newcoms -->
  <div id="rssdiv">
    <a href="blog.rss">RSS</a>
  </div>
</div> <!-- end of left -->
<div id="article">
<?php

      $MQ = "SELECT EntrTime,Title,EntrText FROM tblEntries WHERE EntrID=" . $EID;
      if(!($RESULT = mysql_query($MQ,$CON)))
      {
        echo "Blog Entry Query Failed";
      }
      if(!($ROW = mysql_fetch_array($RESULT)))
      {
        echo "Query Did Not Return a Blog Entry";
      }

      echo "<h3>" . $ROW['Title'] . "</h3>";
      echo $ROW['EntrText'];

?>
<hr/>
<h4>Comments</h4>
<?php
      $MQ = "SELECT CommID,CommTime,CommName,CommText from tblComm WHERE EID=" 
           . $EID .  " AND active=1 ORDER BY CommID";
      if(!($RESULT = mysql_query($MQ,$CON)))
      {
        echo "<p class=\"mok\">Comments Query Failed</p>";
      }
      else
      {
        $I = 0;
        while ($ROW = mysql_fetch_array($RESULT))
        {
          echo "<div class=\"onecom\">";
          echo "<p class=\"comtime\"><a name=\"cid" . $ROW['CommID'] . "\">" 
            . $ROW['CommTime'] . "</a>&nbsp;&nbsp;&nbsp;</p>";
          echo "<p class=\"comname\">" . $ROW['CommName'] . "</p>";
          echo "<p class=\"comtext\">" . $ROW['CommText'] . "</p>";
          echo "</div>";
          echo "<hr/>";
          $I = $I + 1;
        }
        if ($I==0)
        {
          echo "<p class=\"mok\">No comments.</p>";
        }
      }
?>
<div class="onecom">
<form action="index.php" method="POST">
  Your Name: <input type="text" name="CNAME" value="<?php if(isset($_POST['CNAME'])) {echo $_POST['CNAME'];} ?>"/><br/>
  Your Comment:<br/><textarea style="width:95%" rows=10 name="CTEXT"><?php echo $CURTEXT; ?></textarea><br/>
  <?php $APA=rand(5,9); $BPA=rand(1,4); 
        echo $APA . " minus " . $BPA . " is: "; 
  ?>
  <input type="text" name="CPA"/>&nbsp;&nbsp;&nbsp;<input
   type="submit" value="Submit Comment" />
  <input type="hidden" name="APA" value="<?php echo $APA; ?>"/>
  <input type="hidden" name="BPA" value="<?php echo $BPA; ?>"/>
  <input type="hidden" name="EID" value="<?php echo $EID; ?>"/>
</form>
<?php
  if($ERRMESG!="")
  { echo "<p class=\"err\">" . $ERRMESG . "</p>"; }
?>
</div>
<?php
      mysql_close($CON);
?>
</div> <!-- end of div article -->

<script type="text/javascript" src="../js/header.js"></script>
</body>
</html>
