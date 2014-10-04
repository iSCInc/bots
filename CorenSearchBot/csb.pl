#! /usr/bin/perl

use Data::Dumper;
use Text::Align::WagnerFischer;
use Digest::MD5 qw(md5_hex);
use LWP::UserAgent;
use HTTP::Cookies;
use HTML::Parse;
use HTML::FormatText;
use URI::Escape qw(uri_escape uri_escape_utf8 uri_unescape);
use JSON;
use Net::OAuth;
use utf8;

$Net::OAuth::PROTOCOL_VERSION = Net::OAuth::PROTOCOL_VERSION_1_0A;

##
## (1) Edit the following values:
##
$WIKI	= 'XXX REPLACE XXX';
$BOT	= 'XXX REPLACE XXX';
$SHORT	= 'XXX REPLACE XXX';
$PWD	= 'XXX REPLACE XXX';
$BOSSK	= 'XXX REPLACE XXX';
$BOSSS	= 'XXX REPLACE XXX';

##
## (2) The following pages/templates are needed on-wiki:
##
##	csb-pageincludes
##	csb-pageincluded
##	csb-wikipage
##	csb-notice-pageincludes
##	csb-notice-pageincluded
##	csb-notice-wikipage
##	User:$BOT/result-no
##	User:$BOT/result-unknown
##	User:$BOT/result-yes
##	User:$BOT/config
##	User:$BOT/exclude
##	User:$BOT/allies
##	User:$BOT/manual
##	User:$BOT/results
##

$SHORT = $BOT if not defined $SHORT;
$wua = LWP::UserAgent->new();
$wua->agent("CorenSearchBot/1.7 en ");
$cookie_jar = HTTP::Cookies->new(file => "./wp_cookies.dat", autosave => 1,);
$wua->cookie_jar($cookie_jar);

$xua = LWP::UserAgent->new();
$xua->agent("CorenSearchBot/1.7 en ");


sub broadcast($$) {
#    my($type, $msg) = @_;
#    my $json = encode_json(
#	{   'cmd' => 'inlinepush',
#	    'params' => {
#		'password' => $APE_PW,
#		'raw' => $type,
#		'channel' => "*$SHORT",
#		'data' => {
#		    'msg' => uri_escape($msg)
#		}
#	    }
#	});
#    if(not fork()) {
#	my $req = HTTP::Request->new(GET => "http://$APE/0/?" . uri_escape("[$json]"));
#	my $res = $xua->request($req);
#	exit 0;
#    }
    return;
}

sub nlines($$$$) {
    my ($nln, $fname, $line, $bcast) = @_;
    my @lines;
    if(open FILE, "<$fname") {
	@lines = <FILE>;
	close FILE;
    }
    unshift @lines, "$line\n";
    $#lines = $nln-1 if $#lines >= $nln;
    if(open FILE, ">$fname~") {
	foreach my $l (@lines) {
	    print FILE $l;
	}
	close FILE;
	rename "$fname~", "$fname";
    }
    broadcast $bcast, join('', @lines) if defined $bcast;
}

sub wikilink($) {
    my ($title) = @_;
    my $uri = '';
    return "<SPAN CLASS=\"wiki\">[[<A HREF=\"$uri\">$title</A>]]</SPAN>";
}

sub urilink($) {
    my ($uri) = @_;
    if(length $uri < 32) {
	return "<SPAN CLASS=\"uri\"><A HREF=\"$uri\">$uri</A></SPAN>";
    }
    return "<SPAN CLASS=\"uri\"><A HREF=\"$uri\">". substr($uri, 0, 30) . "</A>...</SPAN>";
}
    
sub currentwork() {
    my $cc = $stat{'class'};
    my $r = '';
    my($msec,$mmin,$mhou,$mday,$mon,$year,undef,undef,undef) = gmtime;
    my $now = sprintf("%04d-%02d-%02d&nbsp;%02d:%02d&nbsp;UTC", $year+1900, $mon+1, $mday, $mhou, $mmin);
    $r .= "<DIV CLASS=\"$cc\">";
    $r .= "<P CLASS=\"status\"><SPAN CLASS=\"time\">$now</SPAN>$stat{'status'}</P>";
    $r .= "<P CLASS=\"title\">" . wikilink($stat{'title'}) . "</P>" if defined $stat{'title'};

    foreach my $uri (keys %{$stat{'uri'}}) {
	my $pc = 'unknown';
	$pc = 'match' if $stat{'uri'}{$uri} =~ /%/;
	$pc = 'nomatch' if $stat{'uri'}{$uri} =~ /^no/;
	$r .= "<P CLASS=\"line-$pc\">";
	$r .= urilink($uri);
	$r .= "<SPAN CLASS=\"match\">$stat{'uri'}{$uri}</SPAN></P>";
    }
    foreach my $uri (keys %{$stat{'wiki'}}) {
	my $pc = 'unknown';
	$pc = 'match' if $stat{'wiki'}{$uri} =~ /%/;
	$pc = 'nomatch' if $stat{'wiki'}{$uri} =~ /^no/;
	$r .= "<P CLASS=\"line-$pc\">";
	$r .= wikilink($uri);
	$r .= "<SPAN CLASS=\"status\">$stat{'wiki'}{$uri}</SPAN></P>";
    }
    $r .= "</DIV>";
    return $r;
}

sub pagereport($$) {
    my ($why, $uri) = @_;
    my $r = '';
    $r .= "<DIV CLASS=\"match\">";
    $r .= "<P CLASS=\"title\">" . wikilink($stat{'title'}) . "</P>";
    $r .= "<P CLASS=\"line-match\">";
    if($why eq 'wikipage') {
	$r .= wikilink($uri);
	$r .= "<SPAN CLASS=\"status\">match</SPAN></P>";
    } else {
	$r .= urilink($uri);
	$r .= "<SPAN CLASS=\"status\">$stat{'uri'}{$uri}</SPAN></P>";
    }
    $r .= "</DIV>";
    nlines 20, "matches.html", $r, 'match';
}

sub workfinished() {
    if(defined $stat{'title'}) {
	my $c = currentwork;
	nlines 10, "recent.html", $c, 'logged';
	if(open FILE, ">>log.html") {
	    print FILE "$c\n";
	    close FILE;
	}
	print "* $stat{'title'}\n";
	undef %stat;
    }
}

sub ReportStatus($$) {
    ($stat{'class'}, $stat{'status'}) = @_;
    my $c = currentwork;
    print ". $stat{'status'}\t[[$stat{'title'}]]\n";
    nlines 5, "status.html", $c, undef;
    broadcast 'status', $c;
}

sub significant($) {
    my @in = split "\n", $_[0];
    my @out;
    foreach my $l (@in) {
	next if $l =~ m/ Categor(y|ies) /;
	next if $l =~ m/align/;
	my $words = 0;
	if($l =~ m/\b[a-z]{5,}\b/) {
	    $words++ while $l =~ m//g;
	}
	if($l =~ m/\b\*\b/) {
	    $words-=2 while $l =~ m//g;
	}
	next if $words < 3;
#$l .= " [$words]";
	push @out, $l;
    }
    return @out;
}

sub complete($) {
    my @in = split "\n", $_[0];
    my @out;
    foreach my $l (@in) {
	next if $l =~ m/ Categor(y|ies) /;
	push @out, $l;
    }
    return @out;
}

sub tokenize(@) {
    my @t;
    foreach my $l (@_) {
	foreach my $t (split / /, $l) {
	    $t =~ s/(.{3,})ed/\1/;
	    $t =~ s/(.{2,})ing/\1/;
	    $t =~ s/(.{2,})s/\1/;
	    push @t, $t if length($t) > 2;
	}
    }
    return @t;
}

sub statementize($) {
    ($_, undef) = @_;
    s/---*/ /g;
    tr/!-?/ /;
#s/  */ /g;
    s/^ *//g;
    s/ *$//g;
    s/\*([^ .])/\1/g;
    s/\.  */.\n/g;
#while(s/([^. \n]) *([A-Z][a-zA-Z0-9_]*)/\1 */gs) { }
#while(s/\*  *\*/* /gs) { }
    s/\.([A-Z])/\n\1/sg;
    s/  *\././g;
    s/\n\n*/\n/gs;
    s/\.\n/\n/gs;
    return $_;
}

sub normalizewikitext($) {
    ($_, undef) = @_;
    tr/*#/::/;
    s/&lt;ref&gt;.*?&lt;\/ref&gt;/ /igs;
    s/&lt;.*?&gt;/ /igs;
    s/&[^;]*;/ /gs;
    while(s/('''*)(.*?)\1/ \2 /gs) { }
    s/\[\[([^|\]]*)]]/ \1 /gs;
    s/\[\[.*?\|(.*?)]]/ \1 /gs;
    s/\[[^ ]* (.*?)]/ \1 /gs;
    s/\[.*?]/ /gs;
    s/^(===*)(.*?)\1/\2. /g;
    s/\{\|[^*]*\|+([^\{*][^*]*\|+)*\{|("(\\.|[^"\\])*"|'(\\.|[^'\\])*'|.[^\{"'\\]*)/defined $2 ? $2 : ""/gse;
    s/{{.*?}}/ /gs;
    s/^[:*]*\*.*/ /s;
    return statementize $_;
}

sub normalizewebtext($) {
    my $plain = HTML::FormatText->new->format(parse_html($_[0]));
    return statementize $plain;
}

sub WPRequest(@) {
    my $req = HTTP::Request->new(POST => "http://$WIKI/w/api.php");
    $req->content_type('application/x-www-form-urlencoded');
    $req->content(join '&', @_);
    my $res = $wua->request($req);
    return $res->is_success? $res->content: undef;
}

sub WPStartEdit($) {
    my ($title) = @_;
    $title = uri_escape($title);

    my $req = WPRequest('action=query',
			'prop=revisions|info',
			'titles='.($title),
			'rvprop=content|timestamp',
			'intoken=edit',
			'rvlimit=1',
			'format=xml');
    my $et = undef;
    my $st = undef;

    print "XXX StartEdit $title\n";
    if($req =~ m{<page ([^>]*)>.*<rev ([^>]*)>(.*)</rev>.*</page>}s) {
	my($_pi, $_ri, $txt) = ($1, $2, $3);
	$et = $1 if $_pi =~ /edittoken="([^"]*)"/;
	$st = $1 if $_pi =~ /starttimestamp="([^"]*)"/;
	$txt =~ s/&lt;/</gs;
	$txt =~ s/&gt;/>/gs;
	$txt =~ s/&quot;/"/gs;
	$txt =~ s/&amp;/\&/gs;
	return ($title, $et, $st, $txt) if defined $et and defined $st;
    } else {
	$et = $1 if $req =~ /edittoken="([^"]*)"/;
	$st = $1 if $req =~ /starttimestamp="([^"]*)"/;
	return ($title, $et, $st, '') if defined $et and defined $st;
    }
    return undef;
}

sub WPTryEdit($$$$$$) {
    my($title, $et, $more, $txt, $es, $nc) = @_;

    print "XXX TryEdit $title\n";
    my $bot = 'bot=1';
    $bot .= '&nocreate=1' if defined $nc;
    my $req = WPRequest('action=edit',
			'title='.($title),
			'token='.uri_escape($et),
			'starttimestamp='.uri_escape($more),
			'summary='.uri_escape($es),
			$bot,
			'format=xml',
			'maxlag=5',
			'text='.uri_escape($txt));
    $req =~ /result="([^"]*)"/;
    print "XXX Result: $req\n";
    return 1  if $1 eq 'Success';
    return undef;
}

sub WPLogin($$$) {
    my ($uname, $pwd, $token) = @_;
    my $req = WPRequest('action=login',
			'lgname='.uri_escape($uname),
			'lgpassword='.uri_escape($pwd),
			(defined $token)? 'lgtoken='.uri_escape($token): undef,
			'format=xml');
    ReportStatus 'unknown', "Login response...";
    return "Ok"  if $req =~ /result="Success"/;
    if($req =~ /result="NeedToken"/) {
	ReportStatus 'unknown', "Need token";
	return WPLogin($uname, $pwd, $1) if $req =~ /token="([^"]*)"/;
    }
    return undef;
}

sub WPArticle($) {
    my($title) = @_;
    my $req = WPRequest('action=query',
			'prop=revisions',
			'titles='.uri_escape($title),
			'rvprop=content|ids',
			'rvlimit=1',
			'format=xml');
    if($req =~ m{<rev [^>]*>(.*?)<\/rev>}s) {
	my $txt = "$1\n";
	$txt =~ s/&lt;/</gs;
	$txt =~ s/&gt;/>/gs;
	$txt =~ s/&quot;/"/gs;
	$txt =~ s/&amp;/\&/gs;
	return $txt;
    }
    return undef;
}

sub WPNewPages() {
    my $limit = 'rclimit=40';
    $limit = "rcend=$last_ts" if defined $last_ts;
    my $list = WPRequest('action=query',
			 'list=recentchanges',
			 $limit,
			 'rctype=new',
			 'rcnamespace=0',
			 'format=xml');
    my @news;
    my $maxrid = 0;
    my $maxts;
    $last_revid = 0 if not defined $last_revid;
    foreach my $rc ($list =~ m/<rc [^>]*>/ogis) {
	next if not $rc =~ m/title="([^"]*)" .*? revid="([0-9]+)" .* timestamp="([^"]*)"/ois;
	last if $2 <= $last_revid;
	if($2 > $maxrid) {
	    $maxrid = $2;
	    $maxts = $3;
	}
	push @news, $1;
    }
    if($maxrid > $last_revid) {
	$last_revid = $maxrid;
	$last_ts = $maxts;
    }
    return @news;
}

sub WPCreator($) {
    my($title) = @_;
    my $art = WPRequest('action=query',
			'prop=revisions',
			'titles='.uri_escape($title),
			'rvprop=user',
			'rvlimit=1',
			'rvdir=newer',
			'format=xml');
    return $1 if $art =~ m/<rev .*user="([^"]*?)"/s;
    return undef;
}

sub WPEditcount($) {
    my($user) = @_;
    my $uinfo = WPRequest('action=query',
			  'list=users',
			  'ususers='.uri_escape($user),
			  'usprop=editcount',
			  'format=xml');
    return $1 if $uinfo =~ m/<user .*editcount="([0-9]+)"/s;
    return undef;
}


# Using Google may breach the EULA unless special permission is obtained
#
# sub GoogleFind($) {
# my $req = HTTP::Request->new(GET => 'http://www.google.com/search?q='.uri_escape(join(' ',@_)).'&ie=utf-8&oe=utf-8&aq=t&lang_en');
# my $res = $xua->request($req);
# my @uri = $res->content =~ m/<a href="([^<]*?)" CLASS=l>/g;
# $#uri = 3 if $#uri > 3;
# return @uri;
# }

# sub YahooFind($) {
# my $req = HTTP::Request->new(GET => "http://search.yahooapis.com/WebSearchService/V1/webSearch?appid=$APPID&query=".uri_escape(join(' ',@_)).'&results=5&language=en');
# my $res = $xua->request($req);
# my @uri;
# my $r = $res->content;
# die "Overlimit!" if $r =~ m/<Message>Limit/i;
# $r =~ s/<Cache>.*?<\/Cache>//sg;
# my @re = $r =~ m/<Url>([^<]*?)\/?<\/Url>/gs;
# return @re;
# }

sub BOSSFind($) {
    my $req = Net::OAuth->request("request token")->new(
	consumer_key => $BOSSK,
	consumer_secret => $BOSSS,
	request_url => 'http://yboss.yahooapis.com/ysearch/web',
	request_method => 'GET',
	signature_method => 'HMAC-SHA1',
	timestamp => time,
	nonce => md5_hex(rand),
	callback => '',
	extra_params => {
	    q => join(' ', @_),
	    format => 'json'
	}
    );
    $req->sign();
    my $res = $xua->get($req->to_url);
    if($res->is_success) {
	$res = decode_json $res->content;
# print Dumper($res);
	if(defined $res and defined $res->{'bossresponse'} and defined $res->{'bossresponse'}{'web'}) {
	    my %web = %{$res->{'bossresponse'}->{'web'}};
	    my $count = $web{'count'};
	    my @uri = ();
	    $count = 10 if $count>10;
	    for(my $i=0; $i<$count; $i++) {
		my %r = %{$web{'results'}->[$i]};
		push @uri, $r{'url'};
	    }
	    return @uri;
        }
    }
    return undef;
}


sub top3($) {
    my($q) = @_;
    print "  - q=$q\n";
    #my @uri = GoogleFind($q);
    #$q =~ s/"//g;
    my @uri = BOSSFind($q);
    $#uri=5 if $#uri>5;
    SITE:
    foreach my $uri (@uri) {
	next if $uri =~ m/\.[pP][Dd][Ff]/;
	foreach my $q (@web) {
            next SITE if $q eq $uri;
        }
        my $site;
        $site = $1 if $uri =~ m{^[^:]*://([^/]*)/};
        if($site eq $WIKI and $uri=~m{/wiki/}) {
	    $uri =~ s{.*/wiki/(.*)}{\1};
	    $uri = uri_unescape($uri);
	    $uri =~ tr/_/ /;
	    next SITE if $uri =~ m/^User( Talk|):/i;
	    foreach my $q (@enwiki) {
	        next SITE if $q eq $uri;
	    }
	    push @enwiki, $uri;
	    next SITE;
        }
        foreach my $re (@exclude) {
	    next SITE  if $uri =~ $re;
        }
        push @web, $uri;
        return if $#web > 5;
    }
}

sub findmatches($) {
    my $title = $_[0];
    $title =~ s/\(.*?\) *//;
    $title =~ s/ *$//;

    $stat{'title'} = $title;
    $stat{'uri'} = { };
    $stat{'wiki'} = { };
    ReportStatus('unknown', 'Reading article');

    my $article = WPArticle($_[0]);
    my @atokens = tokenize complete normalizewikitext $article;
    my @paras = significant normalizewikitext $article;

    my $why = undef;
    my $score = $config{MinScore};
    my $what = undef;
    my $what_ok;
    my $score_ok = 50000;

    local @web;
    local @enwiki;

    return undef if $#atokens < 20;
    $#atokens = 150 if $#atokens > 150;

    my @uri;
    my $ln = 0;

    ReportStatus('unknown', 'Searching the web');
    foreach my $l (@paras) {
        if($ln==1 or $ln==7 or $ln==($#paras-1)) {
	    if($l =~ m/ (.*)\.?/) {
	        my @tq = split ' ', $1;
	        my @q;
	        my $num = 0;
	        foreach my $w (@tq) {
	            push @q, $w if $w =~ m/[a-zA-Z0-9*]/;
		    $num++ if not $w eq '*';
    		    last if $num > 9;
	        }
	        my $q = join ' ', @q;
		top3 "$title $q";
	    }
        }
        $ln++;
    }
    return undef if $#paras < 0;
    top3 "\"$title\"";

    foreach my $uri (@web) {
	$stat{'uri'}{$uri} = '';
    }
    foreach my $uri (@enwiki) {
	$stat{'wiki'}{$uri} = '';
    }

    open DEBUG, ">debug/".uri_escape($title);
    print DEBUG join(' ', @atokens);

    foreach my $uri (@web) {
	$resultfile = 'result.txt';
	unlink $resultfile;

	$stat{'uri'}{$uri} = 'Compare';
	ReportStatus('unknown', 'Checking for copies');

	$pid = fork();
	die "Unable to fork?" if not defined $pid;
	if(not $pid) {
	    #local $SIG{ALRM} = sub { die "alarm\n" };
	    alarm 20;
	    my $req = HTTP::Request->new(GET => $uri);
	    alarm 0;
	    my $res = $xua->request($req);
	    if($res->is_success) {
		@src = tokenize complete normalizewebtext $res->content;
	    }
	    exit if $#src < 200;
	    $#src = 30000/$#atokens  if $#src*$#atokens > 30000;

	    print DEBUG "\n====$uri====\n";
	    print DEBUG join(' ', @src);
	    my $alignment = Text::Align::WagnerFischer->new(
						    left => \@src,
						    right => \@atokens,
						    weights => [0,1,2]
						   );

	    print DEBUG "\nAlignment cost: ", $alignment->cost();
	    my $dif = abs ($#src-$#atokens);
	    $sina = ($alignment->cost()-$dif)*400/$#src;
	    $ains = ($alignment->cost()-$dif)*400/$#atokens;
	    print DEBUG "\n ($dif $sina $ains)";
	    open RF, ">$resultfile" or die "$resultfile: $!";
	    print RF "$sina\n$ains\nOK\n";
	    close RF;
	    exit;
	}

	exit if not $pid;
	waitpid $pid, 0;
	open RF, "<$resultfile" or next;
	print DEBUG "\n";
	close DEBUG;
	$sina = 0 + <RF>;
	$ains = 0 + <RF>;
	my $ok = <RF>;
	close RF;
	next unless $ok =~ m/OK/;

	my $maybe = 'pageincluded';
	if($ains > $sina) {
	    $maybe = 'pageincludes';
	    $sina = $ains;
	}
	my $need = $config{MinScore};
	$need = ($need*$#atokens)/200 if $#atokens<200;

	$stat{'uri'}{$uri} = "no match";
	if($sina<$need and $sina<$score) {
	    $stat{'uri'}{$uri} = "$maybe (100%)";
	    $stat{'uri'}{$uri} = "$maybe (" . int(100-($sina/10)) . "%)" if $sina>=0;
	}

	print "\t$uri: ", $stat{'uri'}{$uri}, "\n";

	if($sina < $need and $sina < $score) {
	    $why = $maybe;
	    $score = $sina;
	    $what = $uri;
	}
	if($sina < $score_ok) {
	    $score_ok = $sina;
	    $what_ok = $uri;
	}
    }

    foreach my $uri (@enwiki) {
	next if $uri eq $_[0];
	$resultfile = 'result.txt';
	unlink $resultfile;

	$stat{'wiki'}{$uri} = 'Compare';
	ReportStatus('unknown', 'Checking on wiki');

	next if $uri eq $title;

	$pid = fork;
	die "Unable to fork!" if not defined $pid;

	if(not $pid) {
	    my $test = WPArticle($uri);
	    my @src = tokenize complete normalizewikitext $test;
	    exit if $#src < 50;
	    $#src = 30000/$#atokens  if $#src*$#atokens > 30000;
	    my $alignment = Text::Align::WagnerFischer->new(
							left => \@src,
							right => \@atokens,
							weights => [-1,1,2]
						       );
	    my $dif = abs ($#src-$#atokens);
	    $sina = ($alignment->cost()-$dif)*400/$#src;
	    $ains = ($alignment->cost()-$dif)*400/$#atokens;

	    open RF, ">$resultfile" or die "$resultfile: $!";
	    print RF "$sina\n$ains\n";
	    close RF;
	    exit;
	}

	exit if not $pid;
	waitpid $pid, 0;
	open RF, "<$resultfile" or next;
	$sina = <RF>;
	$ains = <RF>;
	close RF;

	$sina = $ains if $ains < $sina;

	$stat{'wiki'}{$uri} = 'no match';

	if($sina<-400 and $sina < $score) {
	    $stat{'wiki'}{$uri} = 'match (' . '?' . '%)';
	    $why = 'wikipage';
	    $what = $uri;
	    $score = $sina;
	}
	if($sina < $score_ok) {
	    $score_ok = $sina;
	    $what_ok = $uri;
	}
    }

    if($score < $config{MinScore}) {
	return ($why, $what, ($score)/10);
    }
    return ('', '', 1000);
}

sub TagPage($$$) {
    my($title, $type, $what) = @_;
    my $tag = "{{csb-$type|1=$what}}";

    my $user = WPCreator($title);
    foreach my $ally (@allies) {
	return "creator trusted" if $user eq $ally;
    }
    $user = "User talk:$user" if defined $user;

    while(1) {
	my($ttl, $token, $more, $text) = WPStartEdit($title);
	return "edit-fail"			if not defined $token;
	return "redirected"			if $text =~ m/^#REDIRECT/i;
#
# Template exclusions are hard-coded here.  You can add or replace
# any of them with no ill effect
#
	return "public-domain"			if $text =~ m/{{CRS\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{CWR\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{DANFS\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{DNB\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{ACMH\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{Catholic\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{Include-USGov\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{usgs-[|}]/i;
	return "attributed"			if $text =~ m/{{SmithDGRBM\s*[|}]/i;
	return "attributed"			if $text =~ m/{{Citizendium\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{Gray's\s*[|}]/i;
	return "attributed"			if $text =~ m/{{wikisource\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{Appletons\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{Cite Appleton/i;
	return "public-domain"			if $text =~ m/{{1911\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{InterPro content/i;
	return "public-domain"			if $text =~ m/{{USGovernment\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{CongBio\s*[|}]/i;
	return "page-split"			if $text =~ m/{{Split\s*[|}]/i;
	return "speedied"			if $text =~ m/{{db/;
	return "marked-copyvio"			if $text =~ m/{{copyvio/;
	return "public-domain"			if $text =~ m/{{NIST-PT\s*[|}]/i;
	return "public-domain"			if $text =~ m/{{USGS-gazetteer\s*[|}]/i;
#
# End of customizable exclusions
#
	return "already-tagged"			if $text =~ m/{{csb-/;
#	return "page-gone"			if length($text)<20;

	ReportStatus('match', "Reporting copy");
	$text = "$tag\n\n" . $text;
	if(WPTryEdit($ttl, $token, $more, $text, "Tagging possible copyvio of $what", 1))
	  {
	    return undef if ($user eq '') or not defined $user; # Page tagged - no user to notify?
	    while(1) {
		my $ec = WPEditcount($user);
		my $who = (defined $ec and $ec>=500)? 'experienced': 'newbie';
		my $which = (int(rand(2)))? 'current': 'test';
		($ttl, $token, $more, $text) = WPStartEdit($user);
		last if not defined $token;
		$text = "{{subst:welcomelaws-$which}}\n" if not defined $text or $text eq '';
		$which = (int(rand(2)))? 'current': 'test';
		$text .= "\n{{subst:csb-notice-$type-$who-$which|$title|url=$what}} ~~~~\n";
		last if WPTryEdit($ttl, $token, $more, $text, "Notifying user of possible copyvio on $title ($who/$which)", undef);
	    }
	    while(1) {
		my $rto = $config{ReportTo};
		my(undef,undef,undef,$mday,$mon,$year,undef,undef,undef) = gmtime;
		$today = sprintf("%04d-%02d-%02d", $year+1900, $mon+1, $mday);
		$rto .= "/$today"  if defined $config{DateReports};
		($ttl, $token, $more, $text) = WPStartEdit($rto);
		last if not defined $token;
		my $esctitle = $title;
		$esctitle =~ s/[)(]/\\$1/g;
		my $re = qr/{{La\|$esctitle}}/s;
		last if $text =~ $re;
		$text = "{{subst:Csb-day|$today}}\n\n" if not $text =~ /{{La/;
		if($type eq 'wikipage') {
		    $text .= "\n* {{La|$title}} &mdash; [[$what]]. [[User:$BOT|$SHORT]] reporting at ~~~~~\n";
		} else {
		    $text .= "\n* {{La|$title}} &mdash; [$what $what]. [[User:$BOT|$SHORT]] reporting at ~~~~~\n";
		}
		last if WPTryEdit($ttl, $token, $more, $text, "Adding possible violation on [[$title]]", undef);
	    }
	    return undef
	  }
    }
}

sub configstatus() {
    undef %config;
    undef @exclude;
    undef @allies;
    foreach $l (split "\n", WPArticle("User:$BOT/config")) {
	$config{$1} = $2  if $l =~ m/ *([A-Za-z]+)=(.*)/;
    }
    foreach $l (split "\n", WPArticle("User:$BOT/exclude")) {
	push @exclude, qr/$1/i  if $l =~ m/ *([^=*][^=]*) *$/;
    }
    foreach $l (split "\n", WPArticle("User:$BOT/allies")) {
	push @allies, $1  if $l =~ m/  *([^=]*)$/;
    }
}

my @npq;

ReportStatus 'unknown', "Logging in";
my $ok = WPLogin($BOT, $PWD, undef);
die "No login" unless defined $ok;
configstatus;

push @npq, @ARGV;
my @manuals;

while(1) {
    workfinished;
    if($#npq < 1) {
	ReportStatus('unknown', "Checking for new pages");
	push @npq, WPNewPages if $#npq < 1;
	if($#npq<0) {
	    if($#manuals<0) {
		foreach $l (split "\n", WPArticle("User:$BOT/manual")) {
		    push @manuals, $1  if $l =~ m/\[\[([^]]*)]]$/;
		}
		while($#manuals >= 0) {
		    my ($ttl, $token, $more, $text) = WPStartEdit("User:$BOT/manual");
		    $text =~ s/==Unprocessed requests==.*==Recent Results==/==Unprocessed requests==\n\n==Recent Results==/s;
		    last if WPTryEdit($ttl, $token, $more, $text, "Removing pending requests", 1);
		}
	    }
	    if($#manuals>=0) {
		my $page = pop @manuals;
		my $result = "{{User:$BOT/result-no|$page|~~~~~}}\n";
		my($why, $what, $score) = findmatches($page);
		$score = int(100-$score);
		$result = "{{User:$BOT/result-unknown|$page|~~~~~}}\n" if $score>-10;
		if(defined $why and not $why eq '') {
		    ReportStatus('match', "Reporting copy");
		    $result = "{{User:$BOT/result-yes|$page|$score|~~~~~|url=$what}}\n";
		} else {
		    ReportStatus('nomatch', "No match");
		}
		while(1) {
		    my ($ttl, $token, $more, $text) = WPStartEdit("User:$BOT/results");
		    $text .= $result;
		    last if WPTryEdit($ttl, $token, $more, $text, "Posting result of manual check", 1);
		}
	    } else {
		ReportStatus('unknown', "Idle");
		sleep 20;
		configstatus;
	    }
	}
    }
    if($#npq >= 0) {
	my $page = $npq[0];
	shift @npq;
	my($why, $what, $score) = findmatches($page);

	if(defined $why and not $why eq '') {
	    $score = int(100-$score);
	    my $res = TagPage($page, $why, $what);
	    if(defined $res) {
		ReportStatus('skipped', "Skipped: $res");
	    } else {
		pagereport($why, $what);
		ReportStatus('match', "Copy reported");
	    }
	} else {
	    ReportStatus('nomatch', "No match");
	}
    }
}

