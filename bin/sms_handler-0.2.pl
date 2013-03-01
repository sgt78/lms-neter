#!/usr/bin/perl -w

# ARGV[0] is the type of event witch can be SEND, RECEIVED, FAILED, REPORT
# ARGV[1] is the filename of sms
# ARGV[3] is the message id. Only used for SEND messages with status report.

if ( $ARGV[0] ne 'RECEIVED' ) {
	exit;
}
 
#Declaration of Perl Modules to use.
use strict;
use DBI;
use DBD::mysql;
use File::Temp qw/ tempfile /;
 
#Declaration of used Constants/Variables
my ($msg_body, $msg_sender , $msg_rcpt, $sthm, $sql, $name, $msg_ts, $msg_ts1, $blad, @all_sms);
my ($fh,$filename,$template);
my $DBuser="root";
my $DBpass="puerros20";

my $rtqueueid = 1; 

# check for incoming sms:

open(SMS,$ARGV[1]);
@all_sms = <SMS>;
close(SMS);

#przerobic na tablice
open(SMS,$ARGV[1]);
while (<SMS>) {
        chomp;
        if ($_ =~ /^From:/) {
                $msg_sender=$_;
                $msg_sender=~ s/^From://g;
                $msg_sender=~ s/^\s+//g;
        next;
        }
        if ($_ =~ /^Sent:/) {
                $msg_ts=$_;
                $msg_ts=~ s/^Sent://g;
                $msg_ts=~ s/^\s+//g;
        next;
        }
        if ($_ =~ /^Received:/) {
                $msg_ts1=$_;
                $msg_ts1=~ s/^Received://g;
                $msg_ts1=~ s/^\s+//g;
        next;
        }
        next if ($_ =~ /^From_SMSC/);
        next if ($_ =~ /^Subject/);
        next if ($_ =~ /^Received/);
        next if ($_ =~ /^$/);
        $msg_body=$_ ;
}
close(SMS);

# rozbieramy body smsa i sprawdzamy prefiksy
my @sms_array = split(/#/, $msg_body);

#ZA - zgłoszenie awarii
if ( uc($sms_array[0]) eq 'ZA' ) {

	my $dbhm=DBI->connect("dbi:mysql:lms:localhost","$DBuser","$DBpass");
	$dbhm->do("SET NAMES utf8");

	my $id_sms=$sms_array[1];
	my $pin_sms=$sms_array[2];
	if (substr($id_sms,0,1) == '0') {
	    $id_sms=substr($id_sms,1);
	}
	my $dbq = $dbhm->prepare("select id, name, lastname, address, zip, city, pin from customers where id = $id_sms and pin = $pin_sms;");
	$dbq->execute();
	#usunąć while
	while (my $row = $dbq->fetchrow_hashref())
	{
		my $cus_id = $row->{'id'};
		my $cus_name = $row->{'name'};
		my $cus_lastname = $row->{'lastname'};
		my $cus_address = $row->{'address'};
		my $cus_zip = $row->{'zip'};
		my $cus_city = $row->{'city'};
		my $cus_pin = $row->{'pin'};

		print "$id_sms $row->{'id'}";

		if ( $id_sms ne $row->{'id'} ) { 
		    $blad = "Nie znaleziono klienta o takim ID! - blad autoryzacji";
		    print $blad;
		    #Wyslanie sms do zglaszajacego
		    $template = 'answer_XXXXXX';
		    ($fh, $filename) = tempfile($template, DIR => '/var/spool/sms/outgoing');
		    open(SMSOUT, '>', $filename);
		    print SMSOUT "To: $msg_sender\n";
		    print SMSOUT "\n";
		    print SMSOUT "$blad Format SMS: ZA#NR_KLIENTA#PIN#INFORMACJA";
		    close(SMSOUT);
		    chmod oct(666), $filename;
		}    
		else
		{
			
			#todo : requestor wyciągać z bazy maila
			my $rt_subject = "[SMS] nr: $msg_sender wysłane: $msg_ts";
			my $dbqts = "UNIX_TIMESTAMP()";
			my $dbq = $dbhm->prepare("select $dbqts as timestamp;");
			$dbq->execute();
			my $row = $dbq->fetchrow_hashref();
			my $timestamp = $row->{'timestamp'};
			my $zapytanie = "insert into rttickets (queueid, customerid, subject, createtime) VALUES ($rtqueueid, $cus_id,'$rt_subject',$timestamp);";
			#print $zapytanie;
			$dbq = $dbhm->prepare($zapytanie);
			$dbq->execute();

			$dbq = $dbhm->prepare("select id from rttickets where queueid = $rtqueueid and subject = '$rt_subject' and createtime = $timestamp;");
			$dbq->execute();
			$row = $dbq->fetchrow_hashref();
			my $rt_ticket_id = $row->{'id'};
	
			$zapytanie = "insert into rtmessages (ticketid, customerid, subject, body, createtime) values ($rt_ticket_id, $cus_id, '$sms_array[3]', '@all_sms', $timestamp);";
			$dbq = $dbhm->prepare($zapytanie);
			$dbq->execute();

			# wysłanie smsów
			# 1. do zgłaszającego
			
			$template = 'answer_XXXXXX';
			($fh, $filename) = tempfile($template, DIR => '/var/spool/sms/outgoing');
			open(SMSOUT, '>', $filename);
			print SMSOUT "To: $msg_sender\n";
			print SMSOUT "\n";
			print SMSOUT "Twoje zgloszenie zostalo przyjete pod numerem $rt_ticket_id. Zgloszenia dostepne sa pod adresem http://www.neter.pl/ebok";
			close(SMSOUT);
			chmod oct(666), $filename;
			# 2. do administratorów

			$template = 'warning_XXXXXX';
			($fh, $filename) = tempfile($template, DIR => '/var/spool/sms/outgoing');
			open(SMSOUT, '>', $filename);
			print SMSOUT "To: +48790899016\n";
			print SMSOUT "\n";
			print SMSOUT "Awaria: $rt_ticket_id, Zglaszajacy: $msg_sender, $cus_name, $cus_lastname, $cus_address, $cus_city, Tresc: $sms_array[3]\n";
			close(SMSOUT);
			chmod oct(666), $filename;

			$template = 'warning_XXXXXX';
			($fh, $filename) = tempfile($template, DIR => '/var/spool/sms/outgoing');
			open(SMSOUT, '>', $filename);
			print SMSOUT "To: +48790899010\n";
			print SMSOUT "\n";
			print SMSOUT "Awaria: $rt_ticket_id, Zglaszajacy: $msg_sender, $cus_name, $cus_lastname, $cus_address, $cus_city, Tresc: $sms_array[3]\n";
			close(SMSOUT);
			chmod oct(666), $filename;

			$template = 'warning_XXXXXX';
			($fh, $filename) = tempfile($template, DIR => '/var/spool/sms/outgoing');
			open(SMSOUT, '>', $filename);
			print SMSOUT "To: +48503607573\n";
			print SMSOUT "\n";
			print SMSOUT "Awaria: $rt_ticket_id, Zglaszajacy: $msg_sender, $cus_name, $cus_lastname, $cus_address, $cus_city, Tresc: $sms_array[3]\n";
			close(SMSOUT);
			chmod oct(666), $filename;
		}
	}

}

if ( uc($sms_array[0]) eq 'OH' ) {

	my @nod_array_answer;
	my $nod_count;

	my $dbhm=DBI->connect("dbi:mysql:lms:hosting.sky24.pl","$DBuser","$DBpass");
	$dbhm->do("SET NAMES utf8");

	my $id_sms=$sms_array[1];
	my $pin_sms=$sms_array[2];
	if (substr($id_sms,0,1) == '0') {
	    $id_sms=substr($id_sms,1);
	}


	my $dbq = $dbhm->prepare("select id, name, lastname, address, zip, city, pin from customers where id = $id_sms and pin = $pin_sms;");
	$dbq->execute();
	#usunąć while
	while (my $row = $dbq->fetchrow_hashref())
	{
		my $cus_id = $row->{'id'};
		my $cus_name = $row->{'name'};
		my $cus_lastname = $row->{'lastname'};
		my $cus_address = $row->{'address'};
		my $cus_zip = $row->{'zip'};
		my $cus_city = $row->{'city'};
		my $cus_pin = $row->{'pin'};
		
		if ( $id_sms ne $row->{'id'} ) { $blad = "Nie znaleziono klienta o takim ID! - blad autoryzacji"; }
		else
		{
		
		my $dbq = $dbhm->prepare("select name,passwd,inet_ntoa(ipaddr) as ipaddr from nodes where ownerid = $cus_id;");
		$dbq->execute();
		while (my $row1 = $dbq->fetchrow_hashref())
		    {
		    
			my $nod_name = $row1->{'name'};
			my $nod_passwd = $row1->{'passwd'};
			my $nod_ipaddr = $row1->{'ipaddr'};
			
			$nod_count = push( @nod_array_answer , "neter.pl autoryzazcja dla IP:$nod_ipaddr LOGIN:$nod_name,HASLO:$nod_passwd;"); 
		    
		    }
		
#		print "  @nod_array_answer .\n";
		#printf $nod_count;
		
		my ($fh, $filename);
		my $template = 'passwd_answer_XXXXXX';
		($fh, $filename) = tempfile($template, DIR => '/var/spool/sms/outgoing');
		open(SMSOUT, '>', $filename);
		print SMSOUT "To: $msg_sender\n";
		print SMSOUT "\n";
		print SMSOUT " @nod_array_answer ";
		close(SMSOUT);
		chmod oct(666), $filename;
		
		
		}		
		
		
	}



}


if ( uc($sms_array[0]) eq 'HELP' ) {

    my $sms_response;
    
    if (uc($sms_array[1]) eq 'ZA') {
	$sms_response = "Zgloszenie awarii format smsa: ZA#nr_klienta#pin#wiadomosc tekstowa << nr klienta i pin znajdziecie Panstwo na kazdej fakturze";
    }
    elsif (uc($sms_array[1]) eq 'OH') {
	$sms_response = "Odzyskanie hasla pppoe format smsa: OH#nr_klienta#pin << nr klienta i pin znajdziecie Panstwo na kazdej fakturze";
    }
    else
    {
	$sms_response = "HELP#ZA << informacje o sposobie zgloszenia awarii HELP#OH << informacje o sposobie odzyskania hasel pppoe";
    }
    
    #wyslanie smsa	
}

exit;
