#!/bin/sh

page=$1
shift;

if [ -z "$page" ]; then
    echo "Usage: $0 <pagename> <email1> [<email2> ...]"
    exit 1
fi

while [ -n "$1" ]; do
#    echo "This is your friendly neighbourhood wiki letting you know that the page $page has changed!
#
#http://nitwiki/?$page" | mail -s "NitWiki: $page has changed" $1 &

    echo "From: webmaster@nit.ca
To: $1
Subject: NitWiki: $page has changed

This is your friendly neighbourhood wiki letting you know that the page $page has changed!

http://nitwiki/?$page" | qmail-inject &
    shift
done
