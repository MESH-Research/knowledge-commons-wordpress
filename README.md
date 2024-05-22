# The Knowledge Commons WordPress stack

## Getting Started

Note: These instructions and many helper scripts assume you are running a Linux-like environment.

### Pre-Requisites

1. [Docker](https://www.docker.com/get-started/) (Necessary for running site locally.)
2. [Lando](https://lando.dev/download/) (Necessary for running site locally.)
   - Set your local machine to trust the [Lando Certificate Authority](https://lando.dev/blog/2020/03/20/5-things-to-do-after-you-install-lando.html).

### Run the site locally

1. Clone this repository: `git clone https://github.com/MESH-Research/knowledge-commons-wordpress.git`
2. Change to the repository directory
3. Run `./lando-rebuild.sh`
4. Visit https://commons-wordpress.lndo.site/

You will probably want to [Import Commons Data](docs/user-content.md) to work with the site.

## Developer Documentation

For further [developer documentation](docs/README.md), see the `docs/` directory.
