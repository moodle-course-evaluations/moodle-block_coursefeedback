# Course Evaluations in Moodle &ndash; block_coursefeedback 

&ndash; Lehrevaluationen in Moodle &ndash;

> [!NOTE]
> **Rewrite**
> 
> Starting in late 2025 / early 2026, this plugin is being completely rewritten as part of a joint project between the
> [Ruhr-Uni Bochum](https://www.ruhr-uni-bochum.de/) and the [Technical University of Berlin](https://www.tu.berlin/). 
> **No PRs** (except perhaps serious security issues) **will be accepted at least until initial development of the rewrite is 
> finished, which is currently planned for Q2/Q3 2026.**
> 
> - The `dev` branch contains the current state of the rewrite.
> - The `MOODLE_401_STABLE` branch contains the latest pre-rewrite state.
> - `MOODLE_27-301_STABLE` and `MOODLE_24-26_STABLE` should be considered deprecated.

BEAM (**B**etter **E**valuation and **A**ssessment in **M**oodle) a.k.a. `block_coursefeedback` is a Moodle plugin that allows conducting student evaluations of teaching (SETs, _🇩🇪 Lehrevaluationen_) entirely within Moodle.

The initial `block_coursefeedback` plugin was developed at the Technical University of Berlin in 2017 with a much narrower scope. Starting at the end of 2025, the Ruhr-University Bochum and the TU Berlin both decided to replace their existing EvaSys-based SET processes and joint development was started by Moodle.NRW and the TU Berlin.

## Features

- [x] Avoid media disruptions and increase response rates by reaching students right in their Moodle courses.
- [x] Create questionnaires using common item types such as single/multiple choice, customizable and reusable Likert scales, free text, etc.
- [x] Plan different evaluations for different course categories.
- [x] Distinguish between lectures, exercises, and other events in a single Moodle course.
- [x] Distinguish between different groups of students (e.g., by their supervisor) in a single teaching event.
- [x] Optionally allow teachers to configure the teaching events in their courses themselves.
- [x] Get a live report of the evaluation results in a single course.
- [ ] Get an aggregated report across an entire faculty/department.
- [ ] Allow teachers to add their own questions to the evaluation questionnaires.
- [ ] Provide well-defined interfaces that allow integration with your institution's infrastructure.

## The current state of this plugin

(As of 2026-07-02.)

- The plugin is in active development, and things may change rapidly.
- The RUB is currently using the plugin in production without major issues. The TUB will likely do so in 2026-07.
- University-specific code is currently part of the plugin. Eventually, there will be well-defined interfaces for either other plugins or subplugins to implement. Basic functionality should be usable by most universities as-is.

## Concepts

<dl>
<dt>Organizations</dt>
<dd>An organization encompasses one or more course categories whose courses are evaluated together. Most settings in BEAM are done at the organization level. Depending on the size and structure of your institution, you might have only a single organization, one per faculty, one per department, etc.</dd>

<dt>Central evaluation administrators</dt>
<dd>Employees who oversee the SETs in the entire university. They manage organizations, may create university-wide questionnaires, and assist faculty evaluation administrators.</dd>

<dt>Faculty evaluation administrators</dt>
<dd>Employees who are responsible for the SETs in their faculties or departments. They create questionnaires, decide what courses are evaluated in what time periods, assign questionnaires to teaching event types (lecture, exercise, etc.), and more.</dd>
</dl>

## Installing via an uploaded ZIP file

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/public/blocks/coursefeedback

Afterward, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License

© 2026 innoCampus, Technische Universität Berlin <br>
© 2026 IT.Services, Ruhr-Universität Bochum

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
