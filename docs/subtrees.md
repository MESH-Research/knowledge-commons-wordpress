# Synchronizing with subtrees

This repository consolidates several previously separate Commons repository into a single repository containing all Commons-managed code necessary for running the Commons site. However, until all Commons environments---from local to staging to production---are containerized, it is necessary to maintain these repositories separately as well.

This repository uses Git subtree to synchronize between itself and legacy Commons repositories. Changes made in this repository are pushed back to the legacy repositories and changes made to the legacy repositories are pulled into this repository.

## Pushing to legacy repositories

```
    Knowledge                    Legacy                   Legacy 
Commons WordPress ->           Repository           ->  Repository
     (main)          (knowledge-commons-wordpress)        (main)
```

Commits from this repository (normally on the `main` branch) are pushed to the `knowledge-commons-wordpress` branch of the legacy repository. They are then merged into that repository's `main` branch.

Example commands: 

- `scripts/subtree-push.php --subtree=core-plugins/hc-custom` (Push changes to `core-plugins/hc-custom` to `hc-custom` repository `knowledge-commons-wordpress` branch.)

- `scripts/subtree-push.php` (Push changes to all repositories on their legacy branches.)

## Pulling from legacy repositories

```
  Legacy         Knowledge             Knowledge
Repository -> Commons WordPress -> Commons WordPress
  (main)         (legacy)               (main)
```

Commits to legacy repositories are pulled from their main branch into the legacy branch of this repository and then merged into main.

Example commands:

- `scripts/subtree-pull.php --subtree=core-plugins/humanities-commons` (Pull changes from the `main` branch of `humanities-commons` repository into the `legacy` branch of this repository.)

- `scripts/subtree-pull.php` (Pull changes from the `main` branches of all legacy repositories to the `legacy` branch of this repository.)

- `scripts/subtree-pull.php --subtree=core-plugins/hc-custom --remote-branch=boss-decoupling --local-branch=boss-decoupling` (Pull changes from the `boss-decoupling` branch into the `boss-decoupling` branch of this repository.)