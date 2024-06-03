# Working with user content

## Accessing Content

To work with user content, you need to import the WordPress database and uploads directory. 

The uploads directory can be accessed through AWS EFS via an NFS mount. You can do this by:

1. Ensure that your local machine can mount NFS v4 shares.
2. Copy the `.lando.efs.yml` file to `.lando.yml` in the root of the project. This configuration should work for macOS, but may need to be adjusted for other OSes, depending on the NFS client being used. (See [NFS configuration](#NFS-Configuration) below.)
3. Connect to the Commons OpenVPN.
4. Rebuild with `./lando-rebuild.sh`.

The database can be synced using the `lando s3-pull` command:

1. Configure the AWS CLI: `lando aws configure`. You will need to obtain CLI user credentials (Currently for Access Key ********K7UZ).
2. Run `lando s3-pull` to list available content.
3. Run `lando s3-pull production-db-full-localized` to import the database. This takes a while (about an hour for me). Note that you need approximately twice the size of the database in free disk space to import it.

### NFS Configuration

For ubuntu, you may need to install the `nfs-common` package. You may also need to adjust your `.lando.yml` file like so:

```yaml
wp_uploads:
	driver: local
	driver_opts:
		type: nfs4
		o: addr=10.100.11.189,ro
		device: ":/"
```

## Logging in to the site

There are two login options for local development:

1) Login using the WordPress native login. When the site is up, run `lando reset-local-passwords` to reset all passwords to 'password'. You can then log in using the WordPress login form.
2) Login using the dev IDMS stack. Once secrets have been imported using `scripts/get-local-secrets.php`, your local instance should be able to login using the dev IDMS stack, as you would from EC2-based dev instances. (Local enrollment flows do not yet exist, so you cannot register using this method.)