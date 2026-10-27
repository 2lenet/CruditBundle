// Fails when assets/dist/crudit.css is not the compilation of assets/styles: dist/ is committed, because the
// bundle ships the compiled CSS (no build step in the projects). The tools of CruditUiBundle (media queries, focus
// ring) are read from vendor/: run `composer install` first. On a tag (CI), the version of assets/package.json must
// be the tag: the bundle has no version of its own in composer.json.
import {readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';

const compiled = execFileSync('npx', [
    'sass',
    '--no-source-map',
    '--style=compressed',
    '--load-path=../vendor/2lenet/crudit-ui-bundle/assets/styles',
    'styles/crudit.scss',
], {encoding: 'utf8'});

if (compiled.trim() !== readFileSync('dist/crudit.css', 'utf8').trim()) {
    console.error('dist/crudit.css is out of date: run `npm run build` in assets/ and commit the result.');
    process.exit(1);
}
console.log('dist/crudit.css is up to date.');

if (process.env.GITHUB_REF_TYPE === 'tag') {
    const tag = process.env.GITHUB_REF_NAME.replace(/^v/, '');
    const {version} = JSON.parse(readFileSync('package.json', 'utf8'));
    if (version !== tag) {
        console.error(`The version of assets/package.json (${version}) is not the tag (${tag}).`);
        process.exit(1);
    }
    console.log(`The version of assets/package.json is the tag (${tag}).`);
}
