#!/bin/bash

log()
{
	echo "$@" >&2
}

getnull()
{
    SRC_SITE="$1"
    log "Source: $SRC_SITE"
    
    # the first line is the site name
    echo "$SRC_SITE"
    
    # no further lines, so we blank out this site
}


getwiki()
{
    SRC_SERVER="$1"
    SRC_USER="$2"
    SRC_PASS="$3"
    SRC_DB="$4"
    SRC_SITE="$5"
    log "Source: $SRC_SITE"

    # the first line is the site name
    echo "$SRC_SITE"
    
    # followed by a bunch of WikiWords on that site
    echo "select distinct title from wiki_pages where bodylength>1;" \
	| mysql -h "$SRC_SERVER" -u "$SRC_USER" -p"$SRC_PASS" "$SRC_DB"
}


getadvo()
{
    SRC_URL="$1"
    SRC_SITE="$2"
    log "Source: $SRC_SITE"
    
    # the first line is the site name
    echo "$SRC_SITE"
    
    # wikiwords for people
    wget -qO - "$SRC_URL" | perl -ne '
    	if (m{<a href="(/.*/)?(\w+?)/">.*?</a>}) { printf("$2\n"); }
    '
}


getclasses()
{
    SRC_SITE="$1"
    SRC_URL="$2"
    
    log "Source: $SRC_SITE"
    
    echo "$SRC_SITE"
    find "$SRC_URL" -maxdepth 1 -name 'class*.html' \
    	| perl -pe 's,^.*class(.*)\.html,$1,' \
    	| grep -ve '-members$'
}


putwiki()
{
    DST_SERVER="$1"
    DST_USER="$2"
    DST_PASS="$3"
    DST_DB="$4"
    DST_SITE="$5"
    RESTRICTED="$6"

    if [ -z "$RESTRICTED" ]; then
        RESTRICTED=0
    fi

    log "Dest: $DST_SITE"

    # the first line is the site name
    read SRC_SITE junk
    
    cat | (
	echo "lock table wiki_remote_pages WRITE;"
	echo "delete from wiki_remote_pages where site='$SRC_SITE';"
	while read name; do
		echo "insert into wiki_remote_pages (site,page,restricted) " \
			"values ('$SRC_SITE', '$name', $RESTRICTED);"
	done
	echo "unlock tables;"
	) \
	| mysql -h "$DST_SERVER" -u "$DST_USER" -p"$DST_PASS" "$DST_DB"
}



OpenNit="server user pw database OpenNit"
NitWiki="server user pw database NitWiki"

getclasses DoxyGen \
  /home/build/auto/HEAD/latest/niti/src/Docs/doxy-html \
  | putwiki $NitWiki 0

getclasses DoxyGen \
  /home/build/auto/HEAD/latest/niti/src/wvstreams/Docs/doxy-html \
  | putwiki $OpenNit 0

#getadvo http://www.advogato.org/person/ AdvoPerson | putwiki $OpenNit
#getadvo http://www.advogato.org/proj/ AdvoProj | putwiki $OpenNit
#getnull AdvoPerson | putwiki $OpenNit
#getnull AdvoProj   | putwiki $OpenNit

getwiki $OpenNit | putwiki $NitWiki 0
getwiki $NitWiki | putwiki $OpenNit 1
