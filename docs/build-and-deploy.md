The Commons WordPress site runs on [Elastic Container Service](https://aws.amazon.com/ecs/), which pulls images from private repositories on [Amazon Elastic Container Registry](https://aws.amazon.com/ecr/). The site is deployed using GitHub Actions. The build process is automated, but the deployment process is manual, using the AWS Management Console.

## Build Process

When a commit is pushed to the `main` or `production` branch, GitHub Actions runs the build process. The build process is defined in `.github/workflows/push-to-aws.yml`. Images on ECR are tagged according to the branch that triggered the build (ie. `main` or `production`). 

To manually build without pushing to GitHub, run the `push-to-aws.sh` script in the root of the repository with the desired tag as an argument. Eg. `./push-to-aws.sh main`.

## Deployment Process

[Production](https://hcommons.org) and [development](https://hcommons-dev.org) are deployed on separate ECS clusters, using the `production` and `main` tagged images respectively. 

Currently the deployment process is manual. To deploy a new build, follow these steps:

1. Log in to the AWS Management Console.
2. In ECS, select the `wordpress-prod-2` cluster.
3. In the `Services` tab, select the `commons-wordpress` service.
4. Click the `Update` button.
5. Change the 'Desired count' to 0 and click `Update`.
6. Wait for the service to stop.
7. Click the `Update` button again.
8. Select the `Force new deployment` option.
9. Change the 'Desired count' to 1 and click `Update`.

(The details are slightly different for development.)