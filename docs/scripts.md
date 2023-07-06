# Knowledge Commons Scripts

The directory contains scripts to facilitate the development and maintenance of the Knowledge Commons site.

## Directory

### subtree-pull.php

Pulls changes from remote subtrees.

Usage: `./subtree-pull.php [<options>]`

#### Options

`--local-branch=<branch>`

Pull into named branch. Default branch is `legacy`.

`--remote-branch=<branch>`

Pull from named branch. Default is the default (HEAD) branch for the remote.

`--subtree=<subtree prexfix>`

Pull for named subtree. Eg. `core-plugins/humanities-commons`. Default is to pull for all subtrees.

### subtree-push.php

Pushes changes on current branch to remote subtrees.

Usage: `./subtree-push.php [<options>]`

#### Options

`--remote-branch=<branch>`

Pushes to named branch. Default is `knowledge-commons-wordpress`.

`--subtree=<subtree prefix>`

Pushes changes only for named subtree. Default pushes changes for all subtrees.

### subtree-status.php

Checks status of remote subtrees, showing default branch, whether there are differences with current local branch, and last commit date for remote.

Usage: `./subtree-status.php`