# radio IRC TCL script
# by SharkMachine
# heavily modified by Super_Flea

# 2/23/2018 - Use http instead of egghttp to make requests to radio website
package require http

array set userit {};

# Edit the following lines for MySQL connection.
set serveri 192.168.1.25;
set useri hidden;
set passwordi hidden;
set databasei hidden;
# don't change this, unless your mySQL server doesn't use default port setting
set mysql_port "default"

#the channel or channels (separated with space) where the script works
set dachan "#test";

#set this to 0 if you don't want it to announce any songs playing
#set this to 1 if you want it to announce every dedicated song
#set this to 2 if you want it to announce every played song
set announce_song 1;

# If you want the bot to be able to request songs, enable
# and set the url to the request enpoint on your webserver (req-irc.php)
set enable_requests 1;
set request_url "http://radio.irc-reactor.com/req-irc.php";

# Set how many days back the top5 requests should search
set request_days 30;

# if you want the script to announce listener peaks, set this to 1
set announce_listener_peak 1;

# set your radio relays here
set relays "http://radio.irc-reactor.com:8018/listen.pls";

# and edit these, if you want.
set help_trigger "!help";
set listen_trigger "!listen";
set listeners_trigger "!listeners";
set next_trigger "!next";
set peak_trigger "!stats";
set playing_trigger "!playing";
set prev_trigger "!prev";
set req_trigger "!play";
set req_alt_trigger "!p";
set search_trigger "!search";
set search_alt_trigger "!s";
set searchnew_trigger "!new";
set top5_trigger "!top5";
set triggers_trigger "!commands";
set vote_trigger "!vote";
set vote_alt_trigger "!v";

# ... and end here
# ---------------------------------------------------------
# and I recommend that you don't look at the code below <.<
# it's really messy and not done in the most optimal way :P

set nickz "";
set textz "";
set chanz "";
set current_song_id 0;

# Bind the triggers to public commands (spoken in channel)
# When the trigger is detected, the eggdrop will call the specified procedure, and pass to it: nick, user, handle, channel, and text
# bind pub <flags> <command> <proc>
# procname <nick> <user@host> <handle> <channel> <text>
# Description: used for commands given on a channel. The first word becomes the command and everything else is the text argument.
bind pub - $help_trigger help;
bind pub - $listen_trigger listen;
bind pub - $listeners_trigger listeners;
bind pub - $next_trigger next;
bind pub - $peak_trigger peak;
bind pub - $playing_trigger playing;
bind pub - $prev_trigger prev;
bind pub - $req_trigger req;
bind pub - $req_alt_trigger req;
bind pub - $search_trigger search;
bind pub - $search_alt_trigger search;
bind pub - $searchnew_trigger searchnew;
bind pub - $top5_trigger top5;
bind pub - $triggers_trigger triggers;
bind pub - $vote_trigger vote;
bind pub - $vote_alt_trigger vote;

# Bind the triggers to private-messaged commands (whispered to bot)
# We essentially just proxy these to the original public commands. We pass the nick in as the channel so the procs can remain simple.
# That way, they are just using putserv to the "channel", which will be either the channel itself, or the nick of the user if it was a PM.
# bind msg <flags> <command> <proc>
# procname <nick> <user@host> <handle> <text>
# Description: used for /msg commands. The first word of the user's msg is the command, and everything else becomes the text argument.
# Also note, putserv "PRIVMSG $dest" works if $dest is a channel or a user nick.
bind msg - $help_trigger help_msg;
bind msg - $listen_trigger listen_msg;
bind msg - $listeners_trigger listeners_msg;
bind msg - $next_trigger next_msg;
bind msg - $peak_trigger peak_msg;
bind msg - $playing_trigger playing_msg;
bind msg - $prev_trigger prev_msg;
bind msg - $req_trigger req_msg;
bind msg - $req_alt_trigger req_msg;
bind msg - $search_trigger search_msg;
bind msg - $search_alt_trigger search_msg;
bind msg - $searchnew_trigger searchnew_msg;
bind msg - $top5_trigger top5_msg;
bind msg - $triggers_trigger triggers_msg;
bind msg - $vote_trigger vote_msg;
bind msg - $vote_alt_trigger vote_msg;

proc help_msg { nick user handle text } 		{ help $nick $user $handle $nick $text; }
proc listen_msg { nick user handle text } 		{ listen $nick $user $handle $nick $text; }
proc listeners_msg { nick user handle text } 	{ listeners $nick $user $handle $nick $text; }
proc next_msg { nick user handle text } 		{ next $nick $user $handle $nick $text; }
proc peak_msg { nick user handle text } 		{ peak $nick $user $handle $nick $text; }
proc playing_msg { nick user handle text } 		{ playing $nick $user $handle $nick $text; }
proc prev_msg { nick user handle text } 		{ prev $nick $user $handle $nick $text; }
proc req_msg { nick user handle text } 			{ req $nick $user $handle $nick $text; }
proc search_msg { nick user handle text } 		{ search $nick $user $handle $nick $text; }
proc searchnew_msg { nick user handle text } 	{ searchnew $nick $user $handle $nick $text; }
proc top5_msg { nick user handle text } 		{ top5 $nick $user $handle $nick $text; }
proc triggers_msg { nick user handle text } 	{ triggers $nick $user $handle $nick $text; }
proc vote_msg { nick user handle text } 		{ vote $nick $user $handle $nick $text; }

bind time - "* * * * *" songi

# Some explanation regarding all of the color codes seen used below
# a color code is started with \003 and it should then ALWAYS be followed by a 2-digit color code. However, they frequently just use the shorthand of a 1-digit color code. E.g. for light red: \00304Hello!\003
# This could cause an issue if the text to be displayed then started with a number, as that first digit would be interpreted as part of the color code.
# Color code according to mIRC: 0 - White, 1 - Black, 2 - Blue, 3 - Green, 4 - Light Red, 5 - Brown, 6 - Purple, 7 - Orange, 8 - Yellow, 9 - Light Green, 10 - Cyan, 11 - Light Cyan, 12 - Light Blue, 13 - Pink, 14 - Grey, 15 - Light Grey
# It seems like you can just keep specifying 2-digit color codes all the way up to 99, and each client treats them differently.
# It's also important to note colors will persist until cleared. \003 will clear color formatting (foreground and background both).
# Foreground and background: \003FF,BB  E.g yellow text on red background: \00308,04Hello
# An example of not cancelling color formatting: \00300,01Hello \00301to you
# The word "Hello" is "White on black", and "to you" is "black on black", as it inherits the black background from the first code.

proc triggers { nick user handle channel texti } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
		set reqs_on '';
		if {$::enable_requests == 1} {set reqs_on $::req_trigger;}
		putserv "PRIVMSG $channel :\00312Radio script triggers:\00312\0033 $::listeners_trigger $::playing_trigger $::next_trigger $::prev_trigger $::search_trigger $::peak_trigger $::help_trigger $::listen_trigger $::top5_trigger $::vote_trigger $::searchnew_trigger $reqs_on \0033";
	}
}

proc searchnew { nick user handle channel texti } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;

  	mysqlsel $h "SELECT artist, title, duration, ID, album FROM `songlist` where count_played = 0 Order By ID desc limit 0,10";

  	mysqlmap $h {artist title duration id album} {

  		set duration [expr $duration / 1000];
  		set min [expr floor($duration / 60.0)];
  		set sec [expr $duration - ($min * 60)];
  		set min [string replace $min [string first . $min] [string last 0 $min] ""];
  		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
  		if {[string length $sec] == 1 } {
  			set sec "0$sec";
  		}
  		set time "$min:$sec";
  		putserv "NOTICE $nick :->\00312 $id\00312 -\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037";
  	}
  	mysqlclose $h;
	}
}

proc vote { nick user handle channel text } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;
  	set currentsong [mysqlsel $h "SELECT songlist.ID FROM historylist,songlist WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S') ORDER BY historylist.date_played DESC LIMIT 0, 1" -flatlist];
  	set songid [lindex $currentsong 0];

  	if { $songid > 0 } {
  	 if { [string is integer $text] && $text > 0 && $text < 6 } {
       # we have a good vote and a song is playing, lets vote it!
      	set votez [mysqlsel $h "Select ID, score From votez Where songID = $songid And host = '$user'" -flatlist];
     		set voteid [lindex $votez 0];
		set myscore [lindex $votez 1];

      	if { [string is integer $voteid] && $voteid > 0 } {
          #already voted
          putserv "PRIVMSG $channel :\0034$nick:\0034\0034 You already voted for this song as $myscore starz.\0034";
        } else {
          #insert new vote!
          set insertid [mysqlexec $h "Insert Into votez (songID, host, score, t_stamp) Values ( $songid, '$user', $text, now())"]
          putserv "PRIVMSG $channel :\0034$nick:\0034\00310 Your vote was successfully entered into the system! \00310";
        }
     } else {
      putserv "PRIVMSG $channel :\0034$nick:\0034\0034 Please vote with a value of 1 - 5 \0034";
     }
  	} else {
      putserv "PRIVMSG $channel :\0034$nick:\0034\0034 No song is currently playing! \0034";
    }
  	mysqlclose $h;
	}
}

proc listen { nick user handle channel texti } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
		putserv "PRIVMSG $channel :\00312 $::relays \00312";
	}
}

proc songi { min hour day month year } {

	global current_song_id;

  if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
	mysqluse $h $::databasei;

	# get the song being played right now
	set newsong [mysqlsel $h "SELECT ID FROM `historylist` Order by date_played Desc limit 0,1;" -flatlist];
	set continue 0;

	# is this song new to us?
	if { [lindex $newsong 0] > $current_song_id } {
	   set current_song_id [lindex $newsong 0];  # yes, update ourselves
	   set continue 1;
	}

	if {$continue == 1} {

		set biisi [mysqlsel $h "SELECT songlist.artist, songlist.title, songlist.duration, songlist.ID, historylist.requestID, historylist.date_played, songlist.album, historylist.listeners FROM historylist,songlist WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S') ORDER BY historylist.date_played DESC LIMIT 0, 1" -flatlist];

		set dedication [mysqlsel $h "SELECT name, msg FROM requestlist WHERE (ID = '[lindex $biisi 4]') LIMIT 0, 1" -flatlist];

		set songid [lindex $biisi 3];
		set duration [expr [lindex $biisi 2] / 1000];
		set min [expr floor($duration / 60.0)];
		set sec [expr $duration - ($min * 60)];
		set min [string replace $min [string first . $min] [string last 0 $min] ""];
		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
		if {[string length $sec] == 1 } {
			set sec "0$sec";
		}
		set time "$min:$sec";

		if {$::announce_song > 0} {
			set announce_it 0;
			set alku "\0034Currently playing:\0034\0033 [lindex $biisi 0] - [lindex $biisi 1] \0033\00310($time)\00310 \0037([lindex $biisi 6])\0037";
			if {$::announce_song == 1} {
				if {[lindex $dedication 0] != ""} {
					set announce_it 1;
				}
			}
			if {$::announce_song == 2} {
				set announce_it 1;
			}
			if {$announce_it == 1} {
				if {[lindex $dedication 0] != ""} {
					# dedicated by
          set alku "$alku\0034 -=-\0034\00310 Dedicated by:\00310\0033 [lindex $dedication 0]\0033";
				}

				#  dedication text
				if {[lindex $dedication 1] != ""} {set alku "$alku\00314 -\00314\0037 [lindex $dedication 1]\0037";}

				# do we have a rating for this song?
    		set avg_score [mysqlsel $h "select avg(score) as gg From votez where songid = $songid group by songid" -flatlist];
    		set avgscore [lindex $avg_score 0];

    		if { [string length $avgscore] > 0 } {
    		  set avg_text "\00312Rating: $avgscore\00312";
    		} else {
          set avg_text "";
        }
        set alku "$alku $avg_text";

				for {set x 0} {$x < [llength $::dachan]} {incr x} {
					putserv "PRIVMSG [lindex $::dachan $x] :$alku";
				}
			}
		}
	}
	mysqlclose $h;
}

proc peak { nick user handle channel texti } {
	global botnick;

	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
		if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
		if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
		mysqluse $h $::databasei;
		set biisi [mysqlsel $h "SELECT max(listeners) from historylist LIMIT 0, 1" -flatlist]

		mysqlclose $h;
		putserv "PRIVMSG $channel :\0033The current listeners record is:\0033\0034 [lindex $biisi 0] people at a time\0034";
	}
}

proc help { nick user handle channel texti } {
	global botnick;

	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {

  	putserv "NOTICE $nick :\0033$botnick command list:\0033";
  	putserv "NOTICE $nick :\00312$::listeners_trigger\00312 \00314-\00314\0033 How many people are listening to the radio";
  	putserv "NOTICE $nick :\00312$::playing_trigger\00312 \00314-\00314\0033 Displays the current song playing on the radio";
  	putserv "NOTICE $nick :\00312$::next_trigger\00312 \00314-\00314\0033 Displays the two songs playing next on the radio";
  	putserv "NOTICE $nick :\00312$::prev_trigger\00312 \00314-\00314\0033 Displays the two songs played previously";
  	putserv "NOTICE $nick :\00312$::peak_trigger\00312 \00314-\00314\0033 Show the listener peak";
  	putserv "NOTICE $nick :\00312$::top5_trigger\00312 \00314-\00314\0033 Show the 5 most recently-requested songs";
  	if {$::enable_requests == 1} {
  		putserv "NOTICE $nick :\00312$::req_trigger\00312 \0037<song id>\0037 \00314-\00314\0033 Request a song to be played. Song ID is shown in \0034$::search_trigger\0034\0033\, \0033\0034$::next_trigger\0034\0033\ and \0033\0034$::prev_trigger\0034";
  		putserv "NOTICE $nick :\00312$::search_trigger\00312 \0037<query>\0037 \00314-\00314\0033 Searches songs by \0034Song ID\0034\0033,\0033 \0034Artist\0034\0033,\0033 \0034Song Title\0034\0033,\0033 \0034Album Name\0034";
  		putserv "NOTICE $nick :\0033 -> For example \00312$::search_trigger symphony\00312 \0033will ouput every song with artist, album or song title \0034symphony\0034";
  	}
  	putserv "NOTICE $nick :\00312$::vote_trigger\00312 \0037<1 - 5>\0037 \00314-\00314\0033 Vote for the current song on a scale of 1 to 5";
	}
}

# handle play requests
proc req { nick user handle channel text } {
  global nickz;
  global textz;
  global chanz;
  if {$::enable_requests == 1} {
    set continue 0;
    for {set x 0} {$x < [llength $::dachan]} {incr x} {
      if { [lindex $::dachan $x] == $channel } { set continue 1; set chanz $channel;}
    }
    if { $nick == $channel } { set continue 1; set chanz $channel; }
    if { $continue == 1 } {

      if { $text == "" } {
        putserv "NOTICE $nick :\00312Give a Song ID\00312";
      }
      if { $text != "" } {
        set nickz $nick;
        set textz $text;
		set reqUrl "$::request_url?songid=$text&host=$user"

		# get token from get request
		set token [http::geturl $reqUrl]
		# get response data from token
		set resp [http::data $token]
		# strip newlines from response so we can work with the data more easily
		regsub -all "\n" $resp "" resp
		# handle the response
		connect_callback $resp
		# dispose of the request
		http::cleanup $token
      }
    }
  }
}

# handle response from http request to radio website
proc connect_callback {html} {

	regexp {<body><i>(.+?)</i></body>} $html - failure
	regexp {<body><b>(.+?)</b></body>} $html - success;

	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
	mysqluse $h $::databasei;

	set biisi [mysqlsel $h "SELECT artist, title, duration, album FROM songlist WHERE ID = '$::textz'" -flatlist];

	if { [lindex $biisi 0] == "" } {
		putserv "PRIVMSG $::chanz :\0034$::nickz:\0034 \0034Your request failed, because:\0034\00312 I'm a useless bot!\00312";
	}

	if { [lindex $biisi 0] != "" } {

		set duration [lindex $biisi 2];
		set duration [expr $duration / 1000];
		set min [expr floor($duration / 60.0)];
		set sec [expr $duration - ($min * 60)];
		set min [string replace $min [string first . $min] [string last 0 $min] ""];
		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
		if {[string length $sec] == 1 } {
			set sec "0$sec";
		}
		set time "$min:$sec";
		set kappale "\0033 [lindex $biisi 0] - [lindex $biisi 1]\0033\00310 ($time)\00310 \0037([lindex $biisi 3])\0037";

		if {[info exists failure] == 0} {
			if {[info exists success] == 0} {
				putserv "PRIVMSG $::chanz :\0034$::nickz:\0034 \0034Your request failed, because:\0034\00312 Could not reach request server.\00312\0034 -=-\0034$kappale";
			}
		}
		if {[info exists success] != 0} {
			putserv "PRIVMSG $::chanz :\0034$::nickz:\0034 \0034Your request was successfully sent to the DJ program\0034\0034 -=-\0034$kappale";
		}
	    if {[info exists failure] != 0} {
			putserv "PRIVMSG $::chanz :\0034$::nickz:\0034 \0034Your request failed, because:\0034\00312 $failure\00312\0034 -=-\0034$kappale";
		}
	}
	mysqlclose $h;
}

proc search { nick user handle channel text } {
	global search_trigger;
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$text == ""} {putserv "NOTICE $nick :\00312Please give search string, for example:\00312\0033 $search_trigger symphony";}

  	if {$text != ""} {

    	regsub -all "\'" $text "\\'" text

    	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
    	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
    	mysqluse $h $::databasei;

    	mysqlsel $h "SELECT artist, title, duration, ID, album FROM songlist WHERE (title like '%$text%') OR (artist like '%$text%') OR (album like '%$text%') OR (ID = '$text') ORDER BY rating DESC LIMIT 0, 10";
    	set i 0;
    	mysqlmap $h {artist title duration id album} {

    		set duration [expr $duration / 1000];
    		set min [expr floor($duration / 60.0)];
    		set sec [expr $duration - ($min * 60)];
    		set min [string replace $min [string first . $min] [string last 0 $min] ""];
    		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
    		if {[string length $sec] == 1 } {
    			set sec "0$sec";
    		}
    		set time "$min:$sec";
    		incr i;
    		putserv "NOTICE $nick :->\00312 $id\00312 -\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037";
    	}

    	if { $i == 0 } { putserv "NOTICE $nick :\00312Couldn't find any songs\00312";
    	}
    	mysqlclose $h;
  	}
	}
}

proc playing { nick user handle channel text } {
	global userit;
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;

    set songinfo [mysqlsel $h "SELECT songlist.artist, songlist.title, songlist.duration, songlist.ID, songlist.album FROM historylist,songlist WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S') ORDER BY historylist.date_played DESC LIMIT 0, 1" -flatlist];
		set songid [lindex $songinfo 3];
		set duration [expr [lindex $songinfo 2] / 1000];
		set min [expr floor($duration / 60.0)];
		set sec [expr $duration - ($min * 60)];
		set min [string replace $min [string first . $min] [string last 0 $min] ""];
		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
		if {[string length $sec] == 1 } {
			set sec "0$sec";
		}
		set time "$min:$sec";
		set alku "\0034Currently playing:\0034\0033 [lindex $songinfo 0] - [lindex $songinfo 1] \0033\00310($time)\00310 \0037([lindex $songinfo 4])\0037";

		set avg_score [mysqlsel $h "select avg(score) as gg From votez where songid = $songid group by songid" -flatlist];
		set avgscore [lindex $avg_score 0];

		if { [string length $avgscore] > 0 } {
		  set avg_text "\00312Rating: $avgscore\00312";
		} else {
      set avg_text "";
    }

  	putserv "PRIVMSG $channel :$alku $avg_text";
  	mysqlclose $h;
	}
}

proc next { nick user handle channel text } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;
  	mysqlsel $h "SELECT songlist.ID, songlist.artist, songlist.title, songlist.duration, songlist.album FROM queuelist, songlist WHERE (queuelist.songID = songlist.ID)  AND (songlist.songtype='S') AND (songlist.artist <> '') ORDER BY queuelist.sortID ASC LIMIT 0, 2";
  	set i 0;
  	mysqlmap $h {songid artist title duration album} {

  		set duration [expr $duration / 1000];
  		set min [expr floor($duration / 60.0)];
  		set sec [expr $duration - ($min * 60)];
  		set min [string replace $min [string first . $min] [string last 0 $min] ""];
  		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
  		if {[string length $sec] == 1 } {
  			set sec "0$sec";
  		}
  		set time "$min:$sec";
  		if { $i == 0 } { set result "\0034Coming next:\0034\00312 $songid -\00312\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037"; }
  		if { $i != 0 } { set result "$result \0034-=-\0034\00312 $songid -\00312\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037"; }
  		incr i;
  	}
  	if { $i == 0 } {
		putserv "PRIVMSG $channel :\0034No upcoming requests at the moment.\0034";
	} else {
		putserv "PRIVMSG $channel :$result";
	}
  	mysqlclose $h;
	}
}

proc prev { nick user handle channel text } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;
  	mysqlsel $h "SELECT songlist.ID, songlist.artist, songlist.title, songlist.duration, songlist.album FROM historylist,songlist WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S') ORDER BY historylist.date_played DESC LIMIT 1, 2"

  	set i 0;
  	mysqlmap $h {songid artist title duration album} {

  		set duration [expr $duration / 1000];
  		set min [expr floor($duration / 60.0)];
  		set sec [expr $duration - ($min * 60)];
  		set min [string replace $min [string first . $min] [string last 0 $min] ""];
  		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
  		if {[string length $sec] == 1 } {
  			set sec "0$sec";
  		}
  		set time "$min:$sec";
  		if { $i == 0 } { set result "\0034Previously played:\0034\00312 $songid -\00312\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037"; }
  		if { $i != 0 } { set result "$result \0034-=-\0034\00312 $songid -\00312\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037"; }
  		incr i;
  	}
  	putserv "PRIVMSG $channel :$result";
  	mysqlclose $h;
	}
}

proc listeners { nick user handle channel text } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
  	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;
  	mysqlsel $h "SELECT listeners FROM historylist ORDER BY date_played DESC LIMIT 0, 1"
  	mysqlmap $h {listeners} {
  		putserv "PRIVMSG $channel :\0034There are $listeners people currently listening to the radio\0034";
  	}
  	mysqlclose $h
	}
}

proc top5 { nick user handle channel text } {
	set continue 0;
	for {set x 0} {$x < [llength $::dachan]} {incr x} {
		if { [lindex $::dachan $x] == $channel } { set continue 1; }
	}
	if { $nick == $channel } { set continue 1; }
	if { $continue == 1 } {
  	if {$::mysql_port == "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi]}
  	if {$::mysql_port != "default"} {set h [mysqlconnect -h  $::serveri -u $::useri -password $::passwordi -port $::mysql_port]}
  	mysqluse $h $::databasei;

  	mysqlsel $h "SELECT songlist.artist, songlist.title, songlist.duration, songlist.ID, songlist.album, count(songlist.ID) as cnt FROM requestlist, songlist WHERE (requestlist.songID = songlist.ID) AND (requestlist.code=200) AND (requestlist.t_stamp BETWEEN NOW() - INTERVAL $::request_days DAY AND NOW()) GROUP BY songlist.ID, songlist.artist, songlist.title ORDER BY cnt DESC Limit 0,5";

  	mysqlmap $h {artist title duration id album cnt} {

  		set duration [expr $duration / 1000];
  		set min [expr floor($duration / 60.0)];
  		set sec [expr $duration - ($min * 60)];
  		set min [string replace $min [string first . $min] [string last 0 $min] ""];
  		set sec [string replace $sec [string first . $sec] [string last 0 $sec] ""];
  		if {[string length $sec] == 1 } {
  			set sec "0$sec";
  		}
  		set time "$min:$sec";
  		putserv "NOTICE $nick :->\0034 Requested $cnt time(s)\0034: \00312 $id\00312 -\0033 $artist - $title \0033\00310($time)\00310 \0037($album)\0037";
  	}
  	mysqlclose $h;
	}
}
