# verbose debugging
set -ex

SERVER=melville.dev
IMAGE_DATE=2015/04

# Get site name from URL parameter. 
# I.e., call this script with "./rollout.sh faq"

SITE=$1

URL=--url=$SITE.$SERVER

# activate another theme and then re-activate our theme
for THEME in twentyfourteen cbox-mla-blog; do wp theme activate $THEME $URL; done

# set the date format to MLA format: 28 April 2015 ('j F Y') 
wp option update date_format 'j F Y' $URL

# define function for setting pictures which we're going to use for certain blogs below
set_picture() { 
	# upload picture
	sudo -u www-data wp media import images/$FILE --title="$NAME" $URL

	# add picture of exec director
	wp widget add text sidebar1 --text="<img src=\"//$SITE.$SERVER/files/$IMAGE_DATE/$FILE\" title=\"$NAME\" class=\"sidebar-image $FULLWIDTH\"><p class=\"sidebar-caption\">$CAPTION</p>" $URL
} 

case $SITE in 
	president)
		NAME='Roland Greene' 
		FILE=roland.jpg
		CAPTION='Roland Greene is the 2015–16 MLA President and Mark Pigott KBE Professor in the School of Humanities and Sciences at Stanford University.'

		# add the widgets
		for WIDGET in archives categories pages recent-posts; do wp widget add $WIDGET sidebar1 $URL; done 

		set_picture
		;;

	execdirector)
		NAME='Rosemary Feal'
		FILE=rosemary.jpg
		CAPTION='Rosemary G. Feal is the executive director of the MLA. Her blog features columns from the <em>MLA Newsletter</em>, resources on the academic workforce and graduate education, and posts about other items of interest to MLA members.'

		# add the widgets
		for WIDGET in archives categories pages recent-posts; do wp widget add $WIDGET sidebar1 $URL; done 

		set_picture
		;;

	executivecouncil)
		NAME='Executive Council'
		FILE=council.jpg
		CAPTION='The Executive Council blog features posts, discussions, and other information about council initiatives.' 
		FULLWIDTH=full-width

		for WIDGET in archives categories recent-comments pages recent-posts; do wp widget add $WIDGET sidebar1 $URL; done 

		set_picture
		;;

	news) 
		# add text widget
		wp widget add text sidebar1 --title='Subscribe' --text='<a href="http://news.commons.mla.org/feed/atom">Subscribe</a> to the News from the MLA feed.' $URL

		# add other widgets
		for WIDGET in archives categories recent-posts; do wp widget add $WIDGET sidebar1 $URL; done 
		;;

	faq)
		# add text widget
		wp widget add text sidebar1 --title='About this FAQ' --text='Some entries here are based on the <a href="http://help.commons.gc.cuny.edu/faq/">FAQ</a> of <em><a href="http://commons.gc.cuny.edu/">CUNY Academic Commons</a></em> and used under the <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA 3.0</a> license.' $URL

		# add other widgets
		for WIDGET in archives categories recent-posts; do wp widget add $WIDGET sidebar1 $URL; done 
		;;

	convention) 
		# add widgets
		for WIDGET in archives categories recent-posts pages; do wp widget add $WIDGET sidebar1 $URL; done 

		# add text widget
		wp widget add text sidebar1 --text='<a href="http://www.mla.org/convention"><img src="https://convention.commons.mla.org/files/2015/04/austin-logo-rendered-smaller.png" alt="Vancouver 2015 convention logo" height="136" width="250"></a>' $URL
		;;

	pmla) 
		# add widgets
		for WIDGET in archives categories recent-posts; do wp widget add $WIDGET sidebar1 $URL; done 
		;;

esac 
