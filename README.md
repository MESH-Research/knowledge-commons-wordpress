# The Knowledge Commons WordPress stack

This is the repository for the [Knowledge Commons Wordpress](https://hcommons.org/) site. It is not a re-usable application and should not be expected to work in any other context than for running the Knowledge Commons site. 

## Developer Getting Started

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

### Build, test and push images locally

`kcb.py` reproduces the GitHub Actions build pipeline on your machine: the
same image tags, build arguments and version resolution, plus the test
suites and the `docker-compose.test.yml` stack. It needs
[uv](https://docs.astral.sh/uv/), Docker and the AWS CLI.

```sh
uv run kcb.py --help
uv run kcb.py info                  # tags this checkout would be pushed with
uv run kcb.py test                  # PHPUnit suites
uv run kcb.py build                 # nginx + app images, tagged as CI would
uv run kcb.py push                  # ECR login and push every tag
uv run kcb.py release               # test, build, push (and bump on main)
uv run kcb.py --dry-run release     # print the commands instead
uv run kcb.py up / down / e2e       # the local test stack
```

See [the local build tool reference](docs/local-build-tool.md) for details.

## Developer Documentation

For further [developer documentation](docs/README.md), see the `docs/` directory.
