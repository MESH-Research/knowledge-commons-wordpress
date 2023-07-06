# The Knowledge Commons WordPress stack

## Getting Started

Note: These instructions and many helper scripts assume you are running a Linux-like environment.

### Pre-Requisites

1. [Docker](https://www.docker.com/get-started/) (Necessary for running site locally.)
2. [Lando](https://lando.dev/download/) (Necessary for running site locally.)
   - Set your local machine to trust the [Lando Certificate Authority](https://lando.dev/blog/2020/03/20/5-things-to-do-after-you-install-lando.html).
3. [PHP 8.2+](https://www.php.net/manual/en/install.php) & [Composer](https://getcomposer.org/doc/00-intro.md) (Necessary for running scripts on host.)

### Run the site locally

1. Clone this repository: `git clone https://github.com/MESH-Research/knowledge-commons-wordpress.git`
2. Change to the repository directory
3. Run `lando start`
4. Visit https://commons-wordpress.lndo.site/

### Importing Commons Data

To work on the Commons site, you will probably need to import content. This is restricted to authorized Commons developers as it involves connecting to live services and having access to users data.

1. Install [AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html)
2. [Configure AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/cli-authentication-user.html#cli-authentication-user-configure.title) (You will need to obtain credentials from another developer.)
3. Run `scripts/get-local-secrets.php` to install local secrets for connecting to IDMS dev stack, Fedora, etc.
4. Run `scripts/s3-pull.php` to see available content exports.
5. Run `scripts/s3-pull.php <prefix>` to import content.

## Developer Documentation

For further [developer documentation](docs/README.md), see the `docs/` directory.
