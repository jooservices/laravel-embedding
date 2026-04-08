# Template: Tag and Publish

## Goal

Create a release tag and trigger automated release and publish flow.

## Steps

1. Create tag:

```bash
git tag vX.Y.Z
```

2. Push tag:

```bash
git push origin vX.Y.Z
```

3. Monitor `.github/workflows/release.yml` run.
4. Verify GitHub release notes and artifacts.
5. Verify package appears on Packagist.

## Exit Criteria

- Release workflow is successful.
- GitHub release is present.
- Packagist version is updated.
