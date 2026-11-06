# Tarsus learning activity for Moodle (mod_tarsus)

An activity module that puts the Tarsus AI learning
coach into a course. Learners work with the material of the course in three
modes; teachers decide which material the coach may use and follow the class.

Requires the connector plugin **local_tarsus**, which holds the credentials
and indexes the material. See its README for the whole plugin set.

## For learners

- **Ask a question.** A chat grounded in the included course material. The
  assistant answers from the material, with formulas, tables and images, and
  learners can ask about any learning card ("Ask about this").
- **Test knowledge.** Practice questions in several types (single choice,
  true/false, open, rule of three, costing tables) at the Bloom taxonomy
  levels the teacher allows, with feedback and experience points. The
  difficulty follows the learner at the chosen pace.
- **Build knowledge.** Learning units with objectives, slides and check
  questions, optionally read aloud, following a didactic method such as
  micro-learning.
- Two **interaction types** per activity: *AI manager-driven* keeps the text
  answers and slide cards configured by the teacher; *Agent-driven* lets the
  Tarsus agent answer with interactive cards it builds itself.
- Progress and experience points feed the gradebook and activity completion.
  The interface follows the site's light or dark colour mode and works on
  phones.

## For teachers

- A **material tab** lists the pages, files and URLs of the course with their
  state in the brain. Teachers include or exclude each item; the connector
  indexes and re-indexes in the background.
- Per activity: interaction type, qualification level (DQR), taxonomy
  levels, subject area, anti-cheat, the three modes with their options
  (conversation style, operator taxonomy, learning pace, learning method,
  GIF animations).
- A **reset** action clears a learner's conversation with the coach
  (capability `mod/tarsus:resetchat`).
- Backup and restore carry the settings and the included material; learner
  progress is restored when user data is included.

## Requirements

- Moodle 5.0 or later, tested on 5.2 and 5.3; PHP 8.2 or later.
- local_tarsus installed and configured (or in mock mode).
- **MathJax filter for formulas.** The brain writes formulas in TeX with
  single-dollar delimiters (`$V=2\cdot7$`). Enable the *MathJax loader*
  filter and add the delimiters to its *MathJax configuration*
  (Site administration › Plugins › Filters › MathJax loader):

  ```json
  {"tex":{"inlineMath":[["$","$"],["\\(","\\)"]],"displayMath":[["$$","$$"],["\\[","\\]"]],"processEscapes":true}}
  ```

## Installation

1. Install and configure local_tarsus.
2. Unzip into `mod/tarsus` (Moodle 5.1+: `public/mod/tarsus`), or add the
   repository as a Git submodule at that path.
3. Visit *Site administration › Notifications* to install.
4. Add a *Tarsus* activity to a course, include material on its material tab
   and let the indexing finish.

## Capabilities

| Capability | Default roles |
|---|---|
| `mod/tarsus:view` | Everyone enrolled |
| `mod/tarsus:addinstance` | Editing teacher, manager |
| `mod/tarsus:manage` | Editing teacher, manager |
| `mod/tarsus:resetchat` | Editing teacher, manager |

## Development

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite mod_tarsus_testsuite
```

JavaScript lives in `amd/src` and is built with Moodle's grunt. The GitHub
workflow runs moodle-plugin-ci on Moodle 5.2 and 5.3.

## Credits

Developed by Vadym Nersesov, lern.link GmbH. Tarsus and the Flexudy Brain are
products of Tarsus GmbH.

## License

GNU GPL v3 or later: https://www.gnu.org/licenses/
