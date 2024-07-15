# Change this to :production when ready to deploy the CSS to the live server.
environment = :production
#environment = :development

# Location of the theme's resources.
css_dir         = "/"
sass_dir        = "/scss"
images_dir      = "/images"
javascripts_dir = "/js"

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
output_style = (environment == :development) ? :expanded : :compressed

# SourceMap
# Produces maps only during development
sourcemap = (environment == :development) ? true : false

# Line Comments
# To disable debugging comments that display the original location of your selectors. Uncomment:
line_comments = (environment == :development) ? true : false

# To enable relative paths to assets via compass helper functions. Since Drupal
# themes can be installed in multiple locations, we don't need to worry about
# the absolute path to the theme from the server root.
relative_assets = true
