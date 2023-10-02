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
3. Run `lando rebuild -y`
4. Visit https://commons-wordpress.lndo.site/

### Importing Commons Data

To work on the Commons site, you will probably need to import content. This is restricted to authorized Commons developers as it involves connecting to live services and having access to users data.

1. Configure AWS CLI: `lando aws configure`
   1. AWS Access Key ID: (get from another developer)
   2. AWS Secrete Access Key: (get from another developer)
   3. Default region name: us-east-1
   4. Default output format: json
2. Run `lando get-local-secrets` to install local secrets for connecting to IDMS dev stack, Fedora, etc.
3. Run `lando s3-pull` to see available content exports.
4. Run `lando s3-pull <prefix>` to import content.
5. Run `lando restart` to relaunch the local site.

## Developer Documentation

For further [developer documentation](docs/README.md), see the `docs/` directory.
