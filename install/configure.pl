#!/usr/bin/perl

# $Id: configure.pl,v 1.7 2002/01/08 20:36:44 smoonen Exp $

if(!(-t))
  { die "You must execute this script from the command line.\n"; }

if($#ARGV == -1)
{
  print "Usage: \n";
  print "  perl ./configure.pl [some/directory/]config.php\n";
  print "\n";
  print "Examples:\n\n";
  print "  perl ./configure.pl ../config.php\n";
  print "  perl ./configure.pl /home/u/user12/tavi/config.php\n";
  exit;
}

# This subroutine prompts the user for a variable's value and writes
# that variable to config.php.
sub do_variable
{
  my ($descriptor, $prompt, $comment) = @_;
  my ($variable, $value);

  if($descriptor =~ /^noprompt_var/)    # Write out value without question.
  {
    $descriptor =~ /(\S+)\s+(\S+)\s+(.+)/;
    $variable = $2;
    $value    = $3;
    print CONFIG $comment;
    print CONFIG "$variable = $value;\n\n";
  }
  elsif($descriptor =~ /^noprompt_const/)  # Write out const without question.
  {
    $descriptor =~ /(\S+)\s+(\S+)\s+(.+)/;
    $variable = $2;
    $value    = $3;
    print CONFIG $comment;
    print CONFIG "define('$variable', $value);\n\n";
  }
  elsif($descriptor =~ /^comment/)      # Write out just a comment.
  {
    print CONFIG "$comment\n";
  }
  else                                  # Prompt user for value.
  {
    system "clear";                     # Clear screen.
    print "$prompt\n";
    if($descriptor =~ /^string/)
    {
      print 'Enter value: ';
      $value = <STDIN>;
      chop $value;
      $value = "'" . $value . "'";
    }
    elsif($descriptor =~ /^boolean/)
    {
      if($descriptor =~ /0\s*$/)        # Default is off.
      {
        print 'Enter setting (y/N): ';
        $value = <STDIN>;
        chop $value;
        if($value =~ /[Yy]/)            # Value is on.
          { $value = 1; }
        else
          { $value = 0; }
      }
      else
      {
        print 'Enter setting (Y/n): ';
        $value = <STDIN>;
        chop $value;
        if($value =~ /[Nn]/)            # Value is off.
          { $value = 0; }
        else
          { $value = 1; }
      }
    }

    $descriptor =~ /(\S+)\s+(\S+)\s+(.+)/;
    $variable = $2;
    print CONFIG $comment;
    print CONFIG "$variable = $value;\n\n";
  }
}

if(!(open SETTINGS , "settings.cnf"))
  { die "Error opening settings.cnf!\n"; }
if(!(open CONFIG , ">$ARGV[0]"))
  { die "Error opening configuration file for writing!\n"; }

print CONFIG "<?php\n\n";

$descriptor = '';

while(<SETTINGS>)
{
  if(/^----$/)                          # New variable.
  {
    if($descriptor ne '')
      { do_variable($descriptor, $prompt, $comment); }

    $descriptor = <SETTINGS>;           # Read descriptor.
    $prompt  = '';
    $comment = '';
  }
  elsif(/^\/\//)                        # Comment for variablle.
    { $comment = $comment . $_; }
  else                                  # Prompt for variable.
    { $prompt = $prompt . $_; }
}

if($descriptor ne '')
  { do_variable($descriptor, $prompt, $comment); }

print CONFIG "?>";
close SETTINGS;
close CONFIG;

print "\n\n$ARGV[0] has been written.  Please examine it to verify\n";
print "it is accurate.\n";

