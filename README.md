# mod_tarsus

Learning activity plugin for the Tarsus ↔ Moodle integration (Lerncoach).

Provides the learner experience (three RAG-grounded modes — *Frage stellen*,
*Wissen testen*, *Wissen aufbauen*) plus the teacher-mode material management
tab (reconciliation list, per-item Brain status, async tracking). Depends only
on the `local_tarsus` client interface.

- **Component:** `mod_tarsus`
- **Target:** Moodle 5.2 · PHP 8.3
- **Status:** mock-first (Phase 1 = *Frage stellen* + teacher mode)

See the project technical specification for full design details.

## Site requirements

- **MathJax filter for formulas.** The brain writes formulas in TeX with
  single-dollar delimiters (`$V=2\cdot7$`), which MathJax does not recognise
  by default. The *MathJax loader* filter must be enabled (it is by default)
  and its **MathJax configuration** setting (Site administration › Plugins ›
  Filters › MathJax loader) must declare the delimiters, e.g.

  ```json
  {"tex":{"inlineMath":[["$","$"],["\\(","\\)"]],"displayMath":[["$$","$$"],["\\[","\\]"]],"processEscapes":true}}
  ```

  The chat module notifies the filter system about every inserted card, so
  formulas also render in content loaded after the page.

## License

GNU GPL v3 or later — https://www.gnu.org/licenses/
