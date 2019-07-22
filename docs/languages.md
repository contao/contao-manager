# Adding a language to the Contao Manager

To support a new language, the following steps are necessary:

1. Register the new language on transifex.com

2. Add the language to the Contao Manager

   - Sync the files with Transifex (`phar tx`).
     Make sure both the .yml and .json file are created.
   - Load the new language files in `src/i18n/index.js`
   - Add the language to `src/i18n/locales.js`

3. Create a new search index for that language on algolia.com
