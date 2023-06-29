# knowledge-commons-wordpress
The Knowledge Commons WordPress stack


## Getting Started

Note: These instructions and many helper scripts assume you are running a Linux-like environment.

1. Clone this repository: `git clone https://github.com/MESH-Research/knowledge-commons-wordpress.git`
2. Install [Lando](https://lando.dev/download/).
3. Change to the repository directory.
4. Run `composer install`
5. Run `lando start`.

### Importing Commons Data

To work on the Commons site, you will probably need to import content. This is restricted to authorized Commons developers as it involves connecting to live services and having access to users data.

1. Install [AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html)
2. [Configure AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/cli-authentication-user.html#cli-authentication-user-configure.title) (You will need to obtain credentials from another developer.)
3. Run `scripts/get-local-secrets.php` to install local secrets for connecting to IDMS dev stack, Fedora, etc.
4. Run `scripts/s3-pull.php` to see available content exports.
5. Run `scripts/s3-pull.php <prefix>` to import content.
