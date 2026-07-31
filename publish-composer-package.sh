#!/usr/bin/env bash

set -euo pipefail

if [[ "$#" -ne 5 ]]; then
    echo "Usage: $0 PACKAGE_DIR PUBLICATION_REPOSITORY_DIR VERSION SOURCE_REPOSITORY SOURCE_COMMIT" >&2
    exit 2
fi

package_dir="$1"
publication_dir="$2"
version="$3"
source_repository="$4"
source_commit="$5"
publication_branch="main"
publication_tag="v${version}"

fail() {
    echo "$1" >&2
    exit 1
}

for command_name in composer cp find git php rm; do
    command -v "${command_name}" >/dev/null || fail "Required command is not installed: ${command_name}"
done

[[ -d "${package_dir}" ]] || fail "Package directory does not exist: ${package_dir}"
[[ -f "${package_dir}/composer.json" ]] || fail "Package composer.json does not exist: ${package_dir}/composer.json"
[[ -d "${publication_dir}/.git" ]] || fail "Publication directory is not a Git worktree: ${publication_dir}"
[[ "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.-]+)?$ ]] || fail "Invalid package version: ${version}"
[[ "${source_repository}" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]] || fail "Invalid source repository: ${source_repository}"
[[ "${source_commit}" =~ ^[0-9a-f]{40,64}$ ]] || fail "Invalid source commit: ${source_commit}"

package_dir="$(cd -- "${package_dir}" && pwd -P)"
publication_dir="$(cd -- "${publication_dir}" && pwd -P)"
publication_root="$(git -C "${publication_dir}" rev-parse --show-toplevel)"
[[ "${publication_root}" == "${publication_dir}" ]] || fail "Publication directory must be the root of its Git worktree."

case "${publication_dir}/" in
    "${package_dir}/"*) fail "Publication directory must not be inside the package directory." ;;
esac
case "${package_dir}/" in
    "${publication_dir}/"*) fail "Package directory must not be inside the publication directory." ;;
esac

current_branch="$(git -C "${publication_dir}" symbolic-ref --short HEAD)"
[[ "${current_branch}" == "${publication_branch}" ]] || fail "Publication repository must be on ${publication_branch}, found ${current_branch}."
[[ -z "$(git -C "${publication_dir}" status --porcelain=v1 --untracked-files=all)" ]] || fail "Publication repository worktree is not clean."

package_version="$(php -r '$data = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR); echo $data["version"] ?? "";' "${package_dir}/composer.json")"
[[ "${package_version}" == "${version}" ]] || fail "Package version ${package_version} does not match requested publication version ${version}."

if git -C "${publication_dir}" ls-remote --exit-code --tags origin "refs/tags/${publication_tag}" >/dev/null 2>&1; then
    fail "Publication tag ${publication_tag} already exists; published versions are immutable."
else
    ls_remote_status="$?"
    [[ "${ls_remote_status}" -eq 2 ]] || fail "Could not check whether publication tag ${publication_tag} exists."
fi

while IFS= read -r -d '' existing_path; do
    rm -rf -- "${existing_path}"
done < <(find "${publication_dir}" -mindepth 1 -maxdepth 1 ! -name .git -print0)
cp -a -- "${package_dir}/." "${publication_dir}/"

php -r '
    $path = $argv[1];
    $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    unset($data["version"]);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
' "${publication_dir}/composer.json"

composer validate --strict --no-check-lock --no-check-version "${publication_dir}/composer.json"
php -r '
    $data = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
    if (($data["name"] ?? null) !== "lucques/conjin" || array_key_exists("version", $data)) {
        fwrite(STDERR, "Published composer.json must describe lucques/conjin and omit the version field.\n");
        exit(1);
    }
' "${publication_dir}/composer.json"

git -C "${publication_dir}" config user.name "github-actions[bot]"
git -C "${publication_dir}" config user.email "41898282+github-actions[bot]@users.noreply.github.com"
git -C "${publication_dir}" add --all
git -C "${publication_dir}" commit --allow-empty -m "Release Conjin ${version}" -m "Built from https://github.com/${source_repository}/commit/${source_commit}."
git -C "${publication_dir}" tag --annotate "${publication_tag}" -m "Conjin ${version}" -m "Built from https://github.com/${source_repository}/commit/${source_commit}."
git -C "${publication_dir}" push --atomic origin "HEAD:refs/heads/${publication_branch}" "refs/tags/${publication_tag}:refs/tags/${publication_tag}"

echo "Published Conjin ${version} as ${publication_tag} from ${source_repository}@${source_commit}."
