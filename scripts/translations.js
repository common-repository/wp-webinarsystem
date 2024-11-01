const glob = require('glob');
const fs = require('fs');
const replace = require('replace-in-file');

const log = console.log; // eslint-disable-line

const checkFile = (res, file, re) => {
  const contents = fs.readFileSync(file);
  let m = null;

  do {
    m = re.exec(contents);
    if (m) {
      const translation = m[1];
      res.add(translation);
    }
  } while (m);

  return res;
};

// options is optional
glob('./wpws-js/src/**/*.{js,jsx,ts,tsx}', {}, function (er, files) {
  const result = new Set();
  files.reduce((res, file) => {
    checkFile(res, file, /translate\(\s*['"](.*?)['"],*\s*\)/g);
    checkFile(res, file, /translateString\(\s*\w+,\s*['"](.*?)['"],*\s*\)/g);
    checkFile(res, file, /getTranslation\(\w+,[ ]?['"](.*?)['"]\)/g);
    return res;
  }, result);

  const phrases = Array.from(result);

  const translationLines = phrases.map(
    phrase => `__('${phrase}', WebinarSysteem::$lang_slug);`,
  );

  fs.writeFileSync(
    './wpws-js/translations.php',
    `
<?php
${translationLines.join('\n')}
?>
`,
  );

  log('Replacing language version');

  replace({
    files: './wpws-js/src/main.js',
    from: /const LANGUAGE_VERSION = \d*;/g,
    to: `const LANGUAGE_VERSION = ${Date.now()};`,
  }).then(() => {
    log('Done');
  });
});
