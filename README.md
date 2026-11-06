<p align="center">
  <a href="https://tarsus.ai"><img src="https://framerusercontent.com/images/bhXmm51vAp3AcBXb7St6VJakI4.webp" alt="The Tarsus AI learning coach: a course with learning goals, explanation cards, exercises and progress" width="920"></a>
</p>

<h1 align="center">Tarsus for Moodle</h1>

<p align="center">
  <b>The AI learning coach as a Moodle activity.</b><br>
  It explains, practises and answers questions – only from the material of the course.
</p>

<p align="center">
  <img alt="Moodle 5.0 or later" src="https://img.shields.io/badge/Moodle-5.0%20%7C%205.2%20%7C%205.3-f98012">
  <img alt="PHP 8.2 or later" src="https://img.shields.io/badge/PHP-8.2%2B-777bb4">
  <img alt="Plugin type: activity module" src="https://img.shields.io/badge/plugin-mod__tarsus-0077be">
  <img alt="Licence GPL-3.0-or-later" src="https://img.shields.io/badge/licence-GPL--3.0--or--later-39ff14">
</p>

<p align="center">
  <a href="https://tarsus.ai/moodle-ki-plugin">Website</a> ·
  <a href="#what-the-coach-does">Features</a> ·
  <a href="#inside-moodle">Screenshots</a> ·
  <a href="#getting-access">Get access</a> ·
  <a href="#installation">Install</a> ·
  <a href="#watch">Videos</a> ·
  <a href="https://www.youtube.com/@tarsus-ai/videos">YouTube</a>
</p>

---

**mod_tarsus** is the Tarsus learning activity for Moodle. Teachers add it to a course like any other activity, pick which pages, files and links the coach may use, and learners get a tutor that is available around the clock: it builds knowledge in small units, tests it with exam-style tasks at the right difficulty, and answers questions in the learner's own words – with the calculation path when the question is a task. Progress and experience points flow into the gradebook and activity completion.

The activity builds on the connector plugin **[local_tarsus](https://github.com/lernlink/moodle-local_tarsus)**, which holds the credentials, indexes the course material and guards every outbound call. Both plugins are free and open source; Tarsus itself is billed by usage.

> **No secrets in this repository.** There are **no API keys and no brain IDs** in the code, the history or the screenshots. The credentials for a real brain are entered once by an administrator in the connector's site settings; they are stored in the Moodle database and never sent to a browser. Without them the plugins run in **mock mode** with simulated answers, so you can explore every screen before you [request access](#getting-access).

**Contents** · [What the coach does](#what-the-coach-does) · [Inside Moodle](#inside-moodle) · [Watch](#watch) · [Getting access](#getting-access) · [The plugin set](#the-plugin-set) · [Requirements](#requirements) · [Installation](#installation) · [First steps in a course](#first-steps-in-a-course) · [Configuration](#configuration) · [Interaction types](#interaction-types) · [Grades, completion and progress](#grades-completion-and-progress) · [Capabilities](#capabilities) · [Data and privacy](#data-and-privacy) · [Development](#development) · [Credits](#credits) · [Licence](#licence)

## What the coach does

### Three ways to learn

| Build knowledge | Test knowledge | Ask a question |
|:--:|:--:|:--:|
| <img src="https://framerusercontent.com/images/s0fmuHNzzQzugFWdZ64BNRzBZVE.webp" alt="Explanation card built from the course material" width="400"> | <img src="https://framerusercontent.com/images/hgagwUKE5rL19zOgU4zCXHDL8wk.webp" alt="Spreadsheet task with line-by-line feedback" width="400"> | <img src="https://framerusercontent.com/images/Gg6PdLZrXoksmnEkebq2iomzcCM.webp" alt="The coach answers a question and shows the calculation path" width="400"> |
| Learning units with objectives, explanation cards and check questions, optionally read aloud. One idea at a time, with examples for the target group. | Tasks like in the exam: single choice, true/false, open questions, rule of three and spreadsheet calculations – with feedback that says what is still missing, experience points and a progress bar. | Questions in the learner's own words, by text or voice, answered from the included material only. Learners can also ask about any learning card or task ("Ask about this"). |

Teachers switch each mode on or off per activity; learners switch between the enabled modes at the bottom of the chat.

### Graphics, simulations and 3D models on demand

| | | |
|:--:|:--:|:--:|
| <img src="https://framerusercontent.com/images/Cosivt9X6HrNKgVnRe4uAuwSXI.webp" alt="Rotatable 3D cylinder with diameter and height" width="400"> | <img src="https://framerusercontent.com/images/uZ93hce6VN7ddmDdqNunRz8mg.webp" alt="Interactive characteristic curve: a slider moves the operating point" width="400"> | <img src="https://framerusercontent.com/images/XuhQqOxDZ7REyDl40HnvMnVjpms.webp" alt="Decision tree: what does the body orbit?" width="400"> |
| **3D models** that learners can turn and measure. | **Simulations** where a slider changes the physics and the result follows. | **Diagrams and animations** drawn for the question at hand. |

With the *Agent-driven* interaction type the coach answers inside Moodle with such interactive cards. See [Interaction types](#interaction-types).

### Many task formats

| | | | |
|:--:|:--:|:--:|:--:|
| <img src="https://framerusercontent.com/images/sdtCvG7M3Q59Nl80mwa2yOOaMTY.webp" alt="Single-choice question with explanation" width="300"> | <img src="https://framerusercontent.com/images/du1FTrsuwAElUTZUEEC5v1euI.webp" alt="Open question with partial credit and feedback on what is missing" width="300"> | <img src="https://framerusercontent.com/images/uNSphTDFSP6R85PZw7wZVvGQK0.webp" alt="True-or-false task with a check button" width="300"> | <img src="https://framerusercontent.com/images/rmkJPlhI2NqsHY2Xt1UFnddNeoU.webp" alt="Calculation chain with two correctly computed values" width="300"> |
| Single choice with an explanation | Open questions with partial credit | True or false | Rule of three and spreadsheet calculations |

### The right level for every person

The coach adapts to the **DQR level**, the **taxonomy stages** (knowledge, understanding, application), the **learning pace** and the **target group** that the teacher sets per activity, and to the **task operators** (standard or the four IHK operators). Which task comes next is decided from the learner's answers – the same idea as in item response theory – so strong learners are stretched and nobody is left behind. Read more in the Tarsus [Praxiswissen](https://tarsus.ai/praxiswissen) articles and watch the explainers under [Watch](#watch).

## Inside Moodle

The learner side, recorded in a Moodle course (the coach speaks the learner's language – this course is in German):

<p align="center">
  <img src="docs/screenshots/12_test_true_false.jpg" alt="Test knowledge in a Moodle course: a true-or-false task is checked and explained, the next task follows; at the bottom the learner asks the coach about the task" width="900">
</p>

| Build knowledge | Ask about a task | Explain it simpler |
|:--:|:--:|:--:|
| <img src="docs/screenshots/10_build_learning_card_audio.jpg" alt="A learning card with narration playing and previous/next navigation" width="440"> | <img src="docs/screenshots/17_ask_about_a_task.jpg" alt="After a wrong answer the learner asks 'what would be right?' and the coach explains the correct assignment" width="440"> | <img src="docs/screenshots/19_ask_explain_simpler.jpg" alt="The learner asks for a simpler explanation and gets one with a small diagram" width="440"> |
| Learning units card by card, read aloud on request. | "What would be right?" – the coach explains the correct answer to the task the learner was just working on. | Mark a passage, ask for a simpler version, get it – with a small diagram. |

| Interactive curve | Animated diagrams | 3D model |
|:--:|:--:|:--:|
| <img src="docs/screenshots/20_ask_interactive_curve.jpg" alt="An interactive characteristic curve with a slider for the voltage at the operating point" width="440"> | <img src="docs/screenshots/21_ask_animated_diagrams.jpg" alt="Two animated diagrams explain diameter and height" width="440"> | <img src="docs/screenshots/22_ask_3d_model.jpg" alt="A rotatable 3D cylinder with diameter and height marked" width="440"> |
| "Please make it interactive" – a slider moves the operating point. | "Please explain with graphics" – the coach draws them. | "I need a 3D animation" – a cylinder to turn with the mouse. |

The teacher side – the material tab, the activity settings and the site-wide connection (shown in mock mode, so every field is a placeholder):

| Material tab | Activity settings |
|:--:|:--:|
| <img src="docs/screenshots/02_material_tab.png" alt="The material tab lists the pages, files and URLs of the course with their index state, an include switch and re-index and remove actions" width="640"> | <img src="docs/screenshots/03_settings_general.png" alt="Activity settings: interaction type, DQR level, taxonomy levels, target group, cheat prevention and the Ask a question options" width="640"> |
| Every page, file and URL of the course with its state in the brain. Switch it on to let the coach use it. | Interaction type, DQR level, taxonomy levels, target group – and one section per mode. |

<details>
<summary><b>All screenshots</b> (23)</summary>

| | |
|:--:|:--:|
| <img src="docs/screenshots/01_course_page.png" alt="A Moodle course page with the Tarsus activity between the pages of a section" width="440"><br><sub>The activity on the course page</sub> | <img src="docs/screenshots/02_material_tab.png" alt="Material tab with indexed and included resources" width="440"><br><sub>Material tab (teacher)</sub> |
| <img src="docs/screenshots/03_settings_general.png" alt="Activity settings: general and Ask a question" width="440"><br><sub>Settings: general, Ask a question</sub> | <img src="docs/screenshots/04_settings_test_build.png" alt="Activity settings: Test knowledge with question types per difficulty, learning pace, and Build knowledge" width="440"><br><sub>Settings: Test knowledge, Build knowledge</sub> |
| <img src="docs/screenshots/05_admin_connection.png" alt="Site settings of the connector: API key, brain ID, base URL and the assistant's identity" width="440"><br><sub>Site settings: connection and assistant (mock values)</sub> | <img src="docs/screenshots/06_admin_guardrails.png" alt="Site settings of the connector: mock mode, kill switch, rate limit and credit budget" width="440"><br><sub>Site settings: guardrails (mock values)</sub> |
| <img src="docs/screenshots/07_admin_brains.png" alt="The Tarsus brains page lists the brains the key can reach with their state" width="440"><br><sub>Tarsus brains page (mock values)</sub> | <img src="docs/screenshots/10_build_learning_card_audio.jpg" alt="Learning card with narration" width="440"><br><sub>Build knowledge: a card, read aloud</sub> |
| <img src="docs/screenshots/11_build_next_card.jpg" alt="The next learning card" width="440"><br><sub>Build knowledge: the next card</sub> | <img src="docs/screenshots/12_test_true_false.jpg" alt="True or false" width="440"><br><sub>Test knowledge: true or false</sub> |
| <img src="docs/screenshots/13_test_single_choice.jpg" alt="Single choice with explanation of the mistake" width="440"><br><sub>Test knowledge: single choice, with the mistake explained</sub> | <img src="docs/screenshots/14_test_open_question.jpg" alt="Open question with feedback on what is missing" width="440"><br><sub>Test knowledge: open question, with feedback on what is missing</sub> |
| <img src="docs/screenshots/15_test_calculation.jpg" alt="Calculation task with two answer fields" width="440"><br><sub>Test knowledge: calculation with two answer fields</sub> | <img src="docs/screenshots/16_test_spreadsheet.jpg" alt="Spreadsheet calculation with line-by-line feedback" width="440"><br><sub>Test knowledge: spreadsheet calculation, checked line by line</sub> |
| <img src="docs/screenshots/17_ask_about_a_task.jpg" alt="Ask about a task" width="440"><br><sub>Ask a question: about the task just answered</sub> | <img src="docs/screenshots/18_ask_search_status.jpg" alt="The coach relates the question to the marked passage and searches the indexed course material" width="440"><br><sub>Ask a question: the coach relates the question to the marked passage and searches the course</sub> |
| <img src="docs/screenshots/19_ask_explain_simpler.jpg" alt="Explain it simpler" width="440"><br><sub>Ask a question: "explain it simpler"</sub> | <img src="docs/screenshots/20_ask_interactive_curve.jpg" alt="Interactive curve" width="440"><br><sub>Ask a question: interactive curve</sub> |
| <img src="docs/screenshots/21_ask_animated_diagrams.jpg" alt="Animated diagrams" width="440"><br><sub>Ask a question: animated diagrams</sub> | <img src="docs/screenshots/22_ask_3d_model.jpg" alt="3D model" width="440"><br><sub>Ask a question: 3D model</sub> |

</details>

## Watch

Explainers from the [Tarsus YouTube channel](https://www.youtube.com/@tarsus-ai/videos) (German):

<table>
  <tr>
    <td align="center" width="33%">
      <a href="https://www.youtube.com/watch?v=JuZLVYBUBLA"><img src="https://img.youtube.com/vi/JuZLVYBUBLA/hqdefault.jpg" alt="Tarsus | So funktioniert die Tarsus Architektur" width="300"></a><br>
      <b>How the Tarsus architecture works</b><br><sub>What happens between a learner's question and the coach's answer</sub>
    </td>
    <td align="center" width="33%">
      <a href="https://www.youtube.com/watch?v=kXTSVUBS9GQ"><img src="https://img.youtube.com/vi/kXTSVUBS9GQ/hqdefault.jpg" alt="E-Learning vs. Rapid Learning" width="300"></a><br>
      <b>E-learning vs. rapid learning</b><br><sub>Why generating the course in the dialogue beats producing it up front</sub>
    </td>
    <td align="center" width="33%">
      <a href="https://www.youtube.com/watch?v=NSif7VgL_w4"><img src="https://img.youtube.com/vi/NSif7VgL_w4/hqdefault.jpg" alt="Tarsus KI-Lerncoach | Bei BZEcom mit Daniel Hobein" width="300"></a><br>
      <b>Tarsus in practice</b><br><sub>The AI learning coach at BZEcom, with Daniel Hobein</sub>
    </td>
  </tr>
</table>

The ideas behind the coach, explained in short articles with videos:

- **[Item response theory with AI](https://tarsus.ai/praxiswissen/item-response-theory)** – why the coach weighs ability and task difficulty instead of counting right answers, and how that picks the next task.
- **[Taxonomies in e-learning](https://tarsus.ai/praxiswissen/dihk-taxonomie-stufen)** – thinking levels and knowledge types after Anderson & Krathwohl, and the "knowledge cube" the coach uses to see strengths, gaps and learning paths.
- **[The learning rate](https://tarsus.ai/praxiswissen/lernrate-adaptives-lernen)** – how adaptive learning updates a learner's level after each answer, and why surprising answers count more.
- **[DQR levels](https://tarsus.ai/praxiswissen/dqr-niveaus)** · **[Avoiding AI hallucinations](https://tarsus.ai/praxiswissen/ki-halluzinationen)** · **[Exam simulations](https://tarsus.ai/praxiswissen/pruefungssimulation-erstellen)** · all articles: [Praxiswissen](https://tarsus.ai/praxiswissen)

## Getting access

The plugins need a Tarsus brain: the API base URL, an **API key** and a **brain ID**. These are issued by Tarsus for your organisation – they are not in this repository and not in any release. Two ways to get the plugin into your Moodle, same plugin either way:

- **Through lern.link**, the integration partner for Moodle: lern.link installs and configures the plugins in your Moodle, advises on the didactic setup and supports the rollout. Write to [info@lernlink.de](mailto:info@lernlink.de?subject=Tarsus%20for%20Moodle), call +49 8152 909090 or visit [lern.link](https://lern.link/).
- **Directly from Tarsus:** write to [info@tarsus.ai](mailto:info@tarsus.ai?subject=Tarsus%20for%20Moodle) or [book an introductory call](https://outlook.office.com/book/Erstgespraech@tarsus.ai/) – 45 minutes, with a course from your own field. More about the product and the plugin on [tarsus.ai/moodle-ki-plugin](https://tarsus.ai/moodle-ki-plugin).

Also good to know:

- **Try the coach first:** a free account on [zero.tarsus.ai](https://zero.tarsus.ai/) lets you upload your own documents and talk to the coach before anything is installed.
- **Pay as you grow:** the plugins cost nothing; Tarsus is billed by usage. Start with one course and extend as needed.
- **On-premise:** Tarsus can also deploy the entire Tarsus stack on your own infrastructure if your organisation requires it. [Talk to us](mailto:info@tarsus.ai?subject=Tarsus%20on-premise).

Until a brain is configured the plugins run in **mock mode** with simulated answers, so administrators and teachers can explore every screen.

## The plugin set

| Plugin | Role | Repository |
|---|---|---|
| **mod_tarsus** (this plugin) | The learning activity: ask, test, build knowledge; material tab; grades and completion | this repository |
| **local_tarsus** | The connector: credentials, brain selection, material indexing, guardrails, mock mode. **Required.** | [lernlink/moodle-local_tarsus](https://github.com/lernlink/moodle-local_tarsus) |
| block_tarsus | Course block with the learner's standing and a teacher summary. Optional. | [lernlink/moodle-block_tarsus](https://github.com/lernlink/moodle-block_tarsus) |
| report_tarsus | Course report (early stage). Optional. | [lernlink/moodle-report_tarsus](https://github.com/lernlink/moodle-report_tarsus) |

Install the connector first; the other plugins declare a dependency on it.

## Requirements

- **Moodle 5.0 or later**, tested on 5.2 and 5.3; **PHP 8.2 or later**.
- **local_tarsus** installed and configured (or in mock mode).
- **MathJax filter for formulas.** The brain writes formulas in TeX with single-dollar delimiters (`$V=2\cdot7$`). Enable the *MathJax loader* filter and add the delimiters to its *MathJax configuration* (Site administration › Plugins › Filters › MathJax loader):

  ```json
  {"tex":{"inlineMath":[["$","$"],["\\(","\\)"]],"displayMath":[["$$","$$"],["\\[","\\]"]],"processEscapes":true}}
  ```

## Installation

Four steps – the first one installs the connector, the next three this activity.

### 1. Install and configure local_tarsus

1. Unzip [local_tarsus](https://github.com/lernlink/moodle-local_tarsus) into `local/tarsus` (Moodle 5.1 or later: `public/local/tarsus`), or add its repository as a Git submodule at that path.
2. Visit *Site administration › Notifications* to install it.
3. Open *Site administration › Plugins › Local plugins › Tarsus* and enter the **API key** and **base URL** you received from Tarsus or lern.link, then pick your brain on the *Tarsus brains* page. No credentials yet? Tick **Mock mode** instead – everything works with simulated answers.

### 2. Put this plugin in place

Unzip the activity into `mod/tarsus` (Moodle 5.1 or later: `public/mod/tarsus`), or add the repository as a Git submodule at that path:

```bash
# Moodle 5.0
git clone https://github.com/lernlink/moodle-mod_tarsus mod/tarsus
# Moodle 5.1 or later
git clone https://github.com/lernlink/moodle-mod_tarsus public/mod/tarsus
```

The folder must be called `tarsus` – Moodle derives the plugin name from it.

### 3. Install it in Moodle

Visit *Site administration › Notifications* and confirm the upgrade. Moodle creates the tables and the capabilities; the activity *Tarsus* then appears in the activity chooser of every course.

### 4. Add the activity to a course

1. Turn on edit mode in a course and add a **Tarsus** activity. Give it a name such as *AI learning coach*; the defaults suit most courses, and every setting can be changed later (see [Configuration](#configuration)).
2. Open the activity. The **material tab** lists the pages, files and URLs of the course. Click *Index this page* (or *Index* per item) and switch the items **on** that the coach may use.
3. Let the indexing finish – the status column turns to *Indexed* – and try the chat preview below the table.

That's it. Learners open the activity and meet the coach; teachers see the material tab and the chat preview, learners only the chat.

### Updating

Replace the folder with the new version (or `git pull` in it) and visit *Site administration › Notifications* again. Settings and learner progress are kept.

## First steps in a course

- **Choose the material deliberately.** The coach answers only from what is switched on. Start with the core script and the exercises; leave out organisational pages.
- **Set the level.** The DQR level, the taxonomy levels and the target group tell the coach whom it is talking to. The task operators (standard or IHK) shape how questions are phrased.
- **Pick the interaction type.** *AI manager-driven* keeps to text answers and the slide cards you configured; *Agent-driven* lets the coach build interactive cards itself. See [Interaction types](#interaction-types).
- **Use the preview.** Below the material table the teacher gets the same chat as the learners, so answers can be checked without leaving the page.
- **Edited a page?** The connector re-indexes it in the background; a deleted item is untracked. *Sync this page with the brain* brings the whole list up to date at once.

## Configuration

### Site settings (local_tarsus)

| Setting | Meaning |
|---|---|
| API key, Brain ID, Base URL | The connection. One brain per site; the key is stored site-wide and never sent to the browser. |
| Tarsus brains | A page listing every brain the key can reach, with its state, to pick the one the site uses. |
| Assistant name, ID, description, avatar | How the assistant appears in the chat. |
| Voice selection, Voice | The voice that reads learning units aloud: by the learner's language, or one fixed voice. |
| Mock mode | Serve all calls from the built-in mock – for trying the plugins without a Tarsus account. |
| Kill switch, Rate limit, Credit budget | Guardrails: block every outbound call at once; cap calls per minute; cap AI credits per period. |

### Activity settings (mod_tarsus)

Every Tarsus activity has its own settings in four sections:

| Section | Settings |
|---|---|
| **AI settings: general** | *Interaction type* (AI manager-driven or Agent-driven) · *Difficulty level (DQR)* from 2-year vocational training to Master Professional · *Taxonomy levels* (knowledge, understanding, application) · *Target group* as free text, e.g. a trade · *Cheat prevention* |
| **AI settings: Ask a question** | *Enable* · *Conversation style*: closed, lexical, tutorial, Socratic or open |
| **AI settings: Test knowledge** | *Enable* · *Task operators*: standard or IHK (name, describe, explain, elaborate) · *Question types* per difficulty grade (easy, medium, hard) out of single choice, true/false, open question, rule of three and spreadsheet calculation · *Learning pace* from ×0.1 to ×2 |
| **AI settings: Build knowledge** | *Enable* · *Add GIFs* · *Didactic concept* (micro-learning) |

At least one mode must be enabled, and every difficulty grade needs at least one question type. The usual Moodle sections follow: availability, restrict access, completion (including *Progress reached*), tags and competencies.

### Material tab

| Column / action | Meaning |
|---|---|
| Status | *Not indexed*, *Pending*, *Awaiting confirmation*, *Indexed*, *Content changed* (edited since indexing), *Pending removal* or *Error* – with a *Retry* action where one helps. |
| Included | Switch an indexed item on or off for this activity. Only indexed items can be included. |
| Index, Re-index, Remove | Send an item to the brain, send it again after edits, or untrack it. |
| Index this page, Sync this page with the brain | Bulk actions for the items on the current page of the list (the brain accepts up to 10 resources per call). |

### Reset a learner's chat

The *Reset chat history* tab (capability `mod/tarsus:resetchat`) clears one learner's conversation with the coach – for example after a demo or when a learner wants a fresh start.

## Interaction types

| Type | Who shapes the answer | What learners see |
|---|---|---|
| **AI manager-driven** (default) | The configuration: conversation style, learning method, question types. | Text answers and slide cards – predictable, close to the material. |
| **Agent-driven** | The Tarsus agent decides how to answer. | Interactive cards built for the question: simulations with sliders, animated diagrams, 3D models, step-by-step calculations – rendered inside the chat. |

Both types answer only from the included material.

## Grades, completion and progress

- **Progress** is the learner's standing in the activity; **experience points (XP)** are earned in *Test knowledge*. Both feed the gradebook.
- **Activity completion** can require a progress value (*Progress reached*), next to the usual manual or view-based completion.
- **Backup and restore** carry the settings and the included material; learner progress is restored when user data is included.
- The interface follows the site's light or dark colour mode and works on phones: on small screens the composer switches to icon-only buttons.

## Capabilities

| Capability | Default roles |
|---|---|
| `mod/tarsus:view` | Everyone enrolled |
| `mod/tarsus:addinstance` | Editing teacher, manager |
| `mod/tarsus:manage` | Editing teacher, manager |
| `mod/tarsus:resetchat` | Editing teacher, manager |

## Data and privacy

- **What is sent to Tarsus:** the text and files of the included course material, the learners' questions and answers, the activity's title and settings, and a pseudonymous learner ID. The brain never receives names or e-mail addresses – each learner is identified by a per-site HMAC pseudonym.
- **Guardrails** on every call: a kill switch, a circuit breaker with capped backoff, a credit budget and a rate limit. A usage ledger records credits per context and user.
- **Privacy API:** the connector implements Moodle's privacy provider for the usage ledger and the pseudonyms; this activity implements it for the learners' progress records.
- The coach does not search the internet. Tarsus stores your content at Microsoft Azure in Germany, GDPR-compliant – and the entire stack can be deployed on-premise on request. Check your data processing agreement with Tarsus before enabling the plugins on a production site.

## Development

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite mod_tarsus_testsuite
```

Unit tests run against the connector's mock client, so they need no Tarsus account. JavaScript lives in `amd/src` and is built with Moodle's grunt. The GitHub workflow runs moodle-plugin-ci on Moodle 5.2 and 5.3. Changes are listed in [CHANGES.md](CHANGES.md).

## Credits

Developed by **Vadym Nersesov**, [lern.link GmbH](https://lern.link/) – Tarsus' integration partner for Moodle and a Moodle Premium Certified Partner. Tarsus is a product of [Tarsus GmbH](https://tarsus.ai).

## Licence

GNU GPL v3 or later: https://www.gnu.org/licenses/

<p align="center"><sub>Our goal is to make high-quality education as accessible as Netflix. – <a href="https://tarsus.ai">tarsus.ai</a></sub></p>
