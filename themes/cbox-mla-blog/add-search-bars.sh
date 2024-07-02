# verbose debugging
set -ex

SERVER=cooper.mlacommons.org

# Get site name from URL parameter. 
# I.e., call this script with "./add-search-bars.sh faq"

SITE=$1

URL=--url=$SITE.$SERVER

case $SITE in 
	president|execdirector|executivecouncil|convention)
		# These blogs have photos up top, so put the search bar below the photos. 
		POSITION=2
		;;

	news|faq|pmla) 
		# These blogs have photos up top, so put the search bar below the photos. 
		POSITION=1
		;;
esac

wp widget add search sidebar1 $POSITION $URL
