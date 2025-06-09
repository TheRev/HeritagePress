# HeritagePress Plugin – Project Instructions

## Table of Contents
- [Phases of Development](#phases-of-development)
- [Notable Links](#notable-links)
- [Naming Conventions](#naming-conventions)
- [Rules & Constraints](#rules--constraints)
- [Workflow for Copilot Chat](#workflow-for-copilot-chat)
- [Testing Requirements](#testing-requirements)
- [Development vs. Production Configuration](#development-vs-production-configuration)
- [Plugin Business Model](#plugin-business-model)
- [Structure](#structure)
- [Glossary](#glossary)
- [Handoff & Continuity](#handoff--continuity)
- [FAQ & Common Pitfalls](#faq--common-pitfalls)
- [Summary](#summary)

---

## Phases of Development
> Refer to phases.md for all requirements and deliverables. Only use phases.md as the master plan.

**HeritagePress** manages family trees and genealogy data. The project is organized into phases, each building on the previous one.

---

## Notable Links
- Plugin directory: `C:\xampp\htdocs\wordpress\wp-content\plugins\heritagepress`
- WordPress debug log: `C:\xampp\htdocs\wordpress\wp-content\debug.log`
- Gedcom 7 Specification: [FamilySearch GitHub](https://github.com/FamilySearch/GEDCOM/tree/main/specification) | [gedcom.io](https://gedcom.io/specifications/FamilySearchGEDCOMv7.html)
- Local XAMPP: `C:\xampp`
- Local PHP: `"C:\xampp\php\php.exe"`

---

## Naming Conventions
- Use PSR-4 namespacing structure with `HeritagePress` namespace
- PHP class files: PSR-4 naming (class name matches filename)
- Use `HERITAGEPRESS_*` for constants
- Use WordPress coding standards

---

## Rules & Constraints
- **Do NOT** recreate or duplicate existing files.
- **Do NOT** use frameworks outside of WordPress core.
- All new features must be added in the appropriate phase and file.
- Keep code modular and organized by responsibility (admin, public, core, etc.).
- Always check `phases.md` for the current phase requirements before coding.
- Use shortcodes and hooks as described in each phase.
- **Composer Usage**: Composer is for development only (autoloading, dev tools, etc.). The final plugin MUST work without Composer or any third-party dependencies in production.

---

## Workflow for Copilot Chat
1. **Reference this file** in every new chat: “Refer to INSTRUCTIONS.md for project context.”
2. **Be explicit** about which file(s) to modify.
3. **Ask for a plan first**: “What files would you change to implement X?”
4. **Do not duplicate files** or create new ones unless the phase requires it.
5. **Follow the phase plan** in `phases.md` before generating code.
6. Use Composer for development only.
7. SQLTools is a VS Code extension connected to the database for direct access.
8. Other extensions (Edge tools, PHP, etc.) may be used as needed.
9. Summarize what was done at the end of each session for continuity.

---

## Testing Requirements
- **Each phase must be thoroughly tested before proceeding to the next**
- Testing should include:
  - Verification that all functionality works as expected
  - Testing edge cases and error handling
  - Checking compatibility with different WordPress versions
  - Ensuring new features don't break existing functionality
  - Validation of any database operations for safety and efficiency
  - Confirmation that the code follows WordPress coding standards
- Document all test results and any issues encountered (see CHECKLIST.md)

---

## Development vs. Production Configuration
- **Development Environment**:
  - Composer can be used for:
    - Autoloading (PSR-4 standard)
    - Development tools (PHP_CodeSniffer, PHPUnit, etc.)
    - Managing development-only dependencies
  - `composer.json` should clearly separate dev dependencies from any production code
- **Production Plugin**:
  - **IMPORTANT**: The final plugin MUST be 100% independent of Composer and any third-party dependencies
  - Uses a custom PHP autoloader included in `autoload.php`
  - All necessary plugin files must be bundled together
  - No external dependencies should be required for installation or operation
  - WordPress administrators should be able to install the plugin using standard methods (upload ZIP or install from dashboard)

---

## Plugin Business Model
- HeritagePress is planned as a freemium/premium plugin
- Core functionality will be available in a free version
- Advanced features will be part of a premium offering
- Code structure should support this distinction from the beginning

---

## Structure
- Modern, namespaced approach using PSR-4 standard
- PHP autoloading (no third-party dependencies for production)
- Organized directory structure with separate concerns (Core, Admin, Frontend, Models)

---

## Glossary
- **Phase**: A major step in the project, as defined in `phases.md`.
- **Core**: Features available in the free version.
- **Premium**: Features available only in the paid version.
- **Shortcode**: WordPress feature for embedding dynamic content.
- **Hook**: WordPress action/filter for extending functionality.
- **Composer**: PHP dependency manager, used for development only.
- **SQLTools**: VS Code extension for database management.

---

## Handoff & Continuity
- At the end of each session, summarize what was done and what remains.
- Log all major decisions, issues, and next steps in CHECKLIST.md.
- When handing off to a new agent or session, copy the last summary and checklist.
- Always check for and resolve merge conflicts if collaborating.
- If picking up mid-phase, review CHECKLIST.md and phases.md for context.

---

## FAQ & Common Pitfalls
- **Q: Where do I find the current requirements?**
  - A: Always in `phases.md`.
- **Q: Can I use Composer in production?**
  - A: No. Composer is for development only.
- **Q: Where do I log test results and issues?**
  - A: In CHECKLIST.md.
- **Q: What if I’m unsure about a requirement?**
  - A: Ask the user or check `phases.md`.
- **Q: What if I need to add a new file?**
  - A: Only add new files if the phase requires it and document in CHECKLIST.md.
- **Common Pitfalls:**
  - Forgetting to check `phases.md` for the latest requirements
  - Using Composer dependencies in production
  - Not documenting test results or decisions
  - Not pausing for user review after major changes

---

## Summary
- Always check `phases.md` for the project outline.
- Complete and test each phase thoroughly before moving on.
- Use and update this INSTRUCTIONS.md as the project evolves.
- Keep all code modular, phase-aligned, and WordPress-compliant.
- The plugin MUST work without Composer or any third-party dependencies in production.
- The free vs. premium features should be clearly separated in the architecture.
- Before you begin, always give a synopsis of what you are going to do and give suggestions for any enhancements to add. At the start, always ask if it's OK to start coding.
- During production, pause along the way when you have certain things done so the user can check through the plugin interface.

---

## File Size Guidelines
- **Keep individual files focused and manageable.**
- Typical file size should be **100–400 lines** for most PHP, JS, or CSS files.
- Avoid files larger than **500–600 lines** unless absolutely necessary (e.g., complex parsers).
- If a file grows too large, split it by responsibility (e.g., separate models, controllers, helpers).
- This improves readability, maintainability, and testing.

---