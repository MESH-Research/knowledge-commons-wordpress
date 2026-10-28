The Commons WordPress site runs on [Elastic Container Service](https://aws.amazon.com/ecs/), which pulls images from private repositories on [Amazon Elastic Container Registry](https://aws.amazon.com/ecr/). The site is deployed using GitHub Actions. The build process is automated, but the deployment process is manual, using the AWS Management Console.

## Build Process

When a commit is pushed to the `main`, `production`, `dev`, `staging` or `test` branch, GitHub Actions runs the build process defined in `.github/workflows/push-to-aws.yml`. Images on ECR are tagged with the branch name and a unique build tag (`v<version>-<sha7>`, prefixed with the branch name outside `main`); on `main` they are also tagged `v<version>` and `latest`, and commitizen bumps `VERSION` and `CHANGELOG.md` once both images have been pushed. The PHP base image that the app image is built from is produced separately by `.github/workflows/build-base-image.yml`.

To run the same build on your own machine, use `kcb.py` in the root of the repository. `uv run kcb.py build` produces the images with exactly the tags CI would apply, `uv run kcb.py push` pushes them to ECR, and `uv run kcb.py release` runs the whole workflow in order. See the [local build tool reference](local-build-tool.md).

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

The equivalent of steps 7 to 9 from the command line, once the images are in ECR, is `uv run kcb.py deploy --cluster <cluster> --service <service>`, which runs `aws ecs update-service --force-new-deployment`.