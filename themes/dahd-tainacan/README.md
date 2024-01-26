# dahd-tainacan-theme

This is just a light fork of the default Tainacan theme, with some modifications made to suit the Digital Art History Directory.

## Building

Initially, most vendor files we installed at build-time via Composer.  To remove the build process from the theme installation, vendor files have to be committed to the repository, along with other compiled assets.  Thus, the original `src` folder has been removed.

For development, install the Node dependencies (Sass) by running `npm ci`.  To compile scss after making changes, run `npm run build` or `npm run sass` (they do the same thing at the present).

## TODO

It would be much better not to ship all of the vendor files with the repo.  I recommend moving Bootstrap and Slick to be Node dependencies instead, removing the need for PHP/Composer.
