# MathLang Solver
## Comprehensive Project Analysis Report

Author: Technical Analysis by GitHub Copilot  
Project Owner: Bornil Mahmud  
Repository: math-tokenazaition  
Date: 2026-04-18

---

## Executive Summary

MathLang Solver is a hybrid software system that combines a compiler-style C parsing engine with a PHP/MySQL web application. The core innovation is allowing users to submit English-like arithmetic sentences and receive deterministic numeric outputs, parse trees, token streams, and step-by-step explanations.

The system is composed of two major subsystems:

1. Language processing and evaluation engine (C + Flex + Bison)
2. Authenticated multi-user web platform (PHP + MySQL)

The project demonstrates strong educational and practical value in these areas:

- Domain-specific language processing
- AST-driven evaluation
- Secure authentication with role-based access
- Historical persistence and admin observability
- Cross-layer integration between native binary and web runtime

This report provides a deep technical analysis of design, architecture, code structure, algorithms, data model, security posture, operations, testing strategy, and roadmap. It is written so it can be submitted as a full academic or industrial project report and expanded further if required by institutional formatting templates.

---

## Table of Contents

1. Project Background and Objectives  
2. Problem Statement  
3. Scope and Functional Boundaries  
4. Technology Stack and Rationale  
5. Repository and Module Structure  
6. System Architecture Overview  
7. Compiler Pipeline: Lexical Analysis  
8. Compiler Pipeline: Parsing and Grammar  
9. AST Model and Tree Lifecycle  
10. Evaluation Engine and Step Generation  
11. Solver JSON Contract and Output Semantics  
12. Web Application Architecture  
13. Authentication and Session Management  
14. Authorization and Role-Based Controls  
15. Database Design and Schema Analysis  
16. Data Flow from UI to Persistent Storage  
17. Frontend and Interaction Design  
18. Build and Runtime Operations  
19. Performance Analysis and Complexity  
20. Security Analysis and Risk Review  
21. Reliability, Error Handling, and Recovery  
22. Testing Strategy and Validation Plan  
23. Quality Findings and Defect Notes  
24. Improvement Roadmap  
25. Deployment Guidance  
26. Conclusion  
27. Appendix A: Key File Walkthrough  
28. Appendix B: Representative Inputs and Outputs  
29. Appendix C: Suggested Documentation Pack for Submission

---

## 1. Project Background and Objectives

### 1.1 Background

Modern educational and assistant tools benefit from interfaces that accept natural phrasing rather than strict symbolic syntax. MathLang Solver addresses this by allowing users to provide arithmetic instructions in sentence form (for example, “sum of 5 and 3 multiplied by 2”) and translating those instructions into a formal evaluable structure.

### 1.2 Primary Objectives

- Parse English-like arithmetic statements.
- Build a consistent Abstract Syntax Tree (AST).
- Evaluate expressions deterministically.
- Produce interpretive output (tokens, AST, steps, expression, result).
- Provide authenticated web access with role-aware features.
- Persist solve history for users and administrators.

### 1.3 Secondary Objectives

- Keep the parser deterministic and maintainable.
- Keep web stack lightweight and easy to run on Windows/XAMPP.
- Make extension pathways visible for future grammar expansion.

---

## 2. Problem Statement

Traditional calculator interfaces require symbolic input like $((5+3)*2)$. Many learners and non-technical users think in words rather than symbolic syntax. The problem is to convert language-like arithmetic instructions into structured mathematical operations, while preserving transparency and reproducibility.

Key constraints:

- Limited grammar coverage must still be unambiguous.
- Error messaging should guide users to valid sentence structures.
- System must avoid silent misinterpretation.
- Multi-user environment requires access control and data isolation.

---

## 3. Scope and Functional Boundaries

### 3.1 In Scope

- Arithmetic operations: add, subtract, multiply, divide
- Prefix and infix sentence forms
- AST visualization as text tree
- Step-by-step operation trace
- User registration/login/logout
- Role-based history and admin panels
- Grammar keyword administration in database

### 3.2 Out of Scope

- Algebraic variables and equation solving
- Functions (sin, cos, log, etc.)
- Unit conversion
- Operator precedence customization by user
- Horizontal scaling and distributed architecture

---

## 4. Technology Stack and Rationale

### 4.1 C, Flex, and Bison for Core Parsing

The project uses a compiler-oriented toolchain:

- Flex to tokenize sentence input.
- Bison to parse token streams using grammar productions.
- C runtime for AST creation, evaluation, and JSON serialization.

Rationale:

- Deterministic parse behavior.
- Strong control over memory and structures.
- Educationally aligned with compiler construction principles.

### 4.2 PHP for Web Layer

PHP is used for authentication, solver invocation, and rendering dashboards. It is practical for XAMPP-based workflows and offers straightforward integration with MySQL via PDO.

### 4.3 MySQL for Persistence

Relational storage supports user accounts, history records, and grammar rule management. This ensures traceability and auditability of solver usage.

### 4.4 Windows-Oriented Build/Run Scripts

Both PowerShell and BAT scripts are present for ease of setup across environments.

---

## 5. Repository and Module Structure

Top-level structure organizes concerns cleanly:

- src: parser and evaluator engine
- web: user-facing app and admin tools
- sql: schema setup and seed records
- scripts: build and runtime automation
- bin: compiled solver binary output

Root entry files provide routing shortcuts into web modules.

---

## 6. System Architecture Overview

MathLang Solver follows a layered flow:

1. User submits text expression in web dashboard.
2. PHP launches solver executable with escaped input.
3. C binary tokenizes and parses expression to AST.
4. Evaluator computes result and explanatory trace.
5. Solver emits JSON to stdout.
6. PHP decodes JSON, renders output, stores history.

This architecture intentionally decouples language-processing complexity from web UI concerns.

Benefits:

- Native parser logic stays isolated and testable.
- Web can be replaced without rewriting parser core.
- JSON contract acts as stable integration boundary.

Tradeoff:

- Process-spawning per request incurs overhead and requires careful operational hardening.

---

## 7. Compiler Pipeline: Lexical Analysis

Lexical specification resides in [src/lexer.l](src/lexer.l). It maps text fragments into parser tokens.

### 7.1 Numeric Tokenization

Rule:

- `[0-9]+(\.[0-9]+)?` produces NUMBER

This supports integers and simple decimal literals.

### 7.2 Keyword Mapping

Examples:

- sum -> SUM
- difference -> DIFFERENCE
- add/plus -> ADD
- multiply/multiplied/times -> MULTIPLY
- divide/divided -> DIVIDE

This alias strategy improves user tolerance for wording variety.

### 7.3 Structure Tokens

Connectors include:

- of, between, and, with, by, then

Parentheses are passed as literal tokens.

### 7.4 Error Tokenization

Unknown characters are recorded and parser error state is populated with a guided suggestion. This improves debugging and user feedback.

---

## 8. Compiler Pipeline: Parsing and Grammar

Grammar resides in [src/parser.y](src/parser.y).

### 8.1 Supported Production Styles

The grammar supports multiple expression forms:

- Prefix style: “sum of A and B”
- Binary style: “A add B”
- Verbal bridge style: “A multiply by B”
- Chained style: “A then add B”

### 8.2 Operator Set

Current operators:

- Addition
- Subtraction
- Multiplication
- Division

### 8.3 Parse Output

Successful parse assigns the root AST node to global parser state. Unsuccessful parse records human-readable error plus suggestion.

### 8.4 Associativity and Precedence

Bison precedence declarations are present for operator ordering. The grammar still emphasizes explicit connectors for readability and safer interpretation.

---

## 9. AST Model and Tree Lifecycle

AST types are defined in [src/ast.h](src/ast.h) and implemented in [src/ast.c](src/ast.c).

### 9.1 Node Types

- NODE_NUMBER
- NODE_BINARY

### 9.2 Operator Enum

- OP_ADD
- OP_SUBTRACT
- OP_MULTIPLY
- OP_DIVIDE

### 9.3 Creation APIs

- ast_make_number
- ast_make_binary

### 9.4 Memory Management

Recursive release via ast_free ensures subtrees are released in depth-first manner.

### 9.5 Symbol and Label Helpers

Operator symbol and display names are centralized. This supports consistent rendering in expression and step output.

---

## 10. Evaluation Engine and Step Generation

Evaluation logic is implemented in [src/evaluator.c](src/evaluator.c).

### 10.1 Evaluation Strategy

Recursive post-order traversal:

1. Evaluate left subtree.
2. Evaluate right subtree.
3. Apply current operator.

For a binary AST with $n$ nodes, time complexity is $O(n)$.

### 10.2 Step Trace

Each evaluated binary node appends a line of form:

- Step k: left op right = value

This gives users an execution narrative aligned with tree reduction order.

### 10.3 Error States

Major runtime validation includes divide-by-zero guard. When denominator equals zero, parser error/suggestion buffers are populated and solve status becomes unsuccessful.

### 10.4 Tree and Expression Reconstruction

Two representational outputs are created:

- Parenthesized expression string
- ASCII tree rendering

These are useful for education, debugging, and explainability.

---

## 11. Solver JSON Contract and Output Semantics

The solver emits JSON with fields such as:

- ok
- input
- expression
- tree
- result
- error
- suggestion
- steps[]
- tokens[]

Serialization includes JSON escaping for control characters, quotes, and separators, minimizing frontend parse risk.

### 11.1 Integration Benefits

The JSON contract decouples binary internals from web rendering and database storage. Any future frontend (CLI, desktop, mobile, API) can consume the same output schema.

---

## 12. Web Application Architecture

Main web entry and dashboard logic is in [web/index.php](web/index.php). Authentication and access logic are in [web/auth.php](web/auth.php).

### 12.1 Request Lifecycle

- User hits dashboard.
- If unauthenticated, login/register card is shown.
- If authenticated and POST submitted, solver runs and result is persisted.
- Dashboard sections show stats, output panel, recent history.

### 12.2 Rendering Pattern

Server-side rendered PHP templates with embedded HTML and small JavaScript enhancements (smooth nav, history filtering, copy output).

### 12.3 Process Invocation

The bridge to native solver is centralized in [web/solver.php](web/solver.php), using proc_open and argument escaping.

---

## 13. Authentication and Session Management

### 13.1 Session Lifecycle

Session starts lazily and login regenerates session ID to reduce fixation risk.

### 13.2 Password Handling

Passwords are hashed using password_hash and validated with password_verify.

### 13.3 Registration Constraints

- Username regex and length controls
- Minimum password length
- Uniqueness checks on username

### 13.4 Bootstrapped Seed Accounts

On first bootstrap, default admin and user accounts are created. This is practical for demo environments but must be managed carefully in production.

---

## 14. Authorization and Role-Based Controls

### 14.1 Roles

- user
- admin

### 14.2 Access Guards

- app_require_login for protected pages
- app_require_admin for admin-only pages

### 14.3 Behavior Differences

- Users see only their own solve history.
- Admins can see all history entries and user identity metadata.
- Admins can create/edit users and grammar rules.

---

## 15. Database Design and Schema Analysis

Schema is defined in [sql/schema.sql](sql/schema.sql).

### 15.1 app_users

Stores identity and access metadata:

- username unique
- display_name
- password_hash
- role enum
- timestamps

### 15.2 history

Stores solving activity:

- user_id (nullable)
- input_text
- output_json
- result_value
- created_at

Indexes support user-based and time-based retrieval.

### 15.3 grammar_rules

Stores keyword mappings used for admin-level metadata management.

### 15.4 Data Integrity Observations

- No foreign key constraints currently defined between history.user_id and app_users.id.
- Application logic still treats relationship semantically.

Recommendation: add explicit foreign key with appropriate delete policy.

---

## 16. Data Flow from UI to Persistent Storage

### 16.1 Solve Submission Path

1. User submits input in dashboard form.
2. PHP trims and validates non-empty input.
3. run_solver executes binary and parses JSON.
4. Input and output JSON are inserted into history.

### 16.2 Read Path

- User history query filtered by user_id.
- Admin history query joins app_users for display attribution.

### 16.3 Data Contract Stability

Because history stores raw output_json, schema remains resilient to minor contract expansion (new optional JSON fields) without immediate migration.

---

## 17. Frontend and Interaction Design

Primary style sheet and layout are provided in [web/assets/clean-dashboard.css](web/assets/clean-dashboard.css).

Features:

- Auth-first split experience (guest vs signed-in)
- Sidebar + section navigation
- Role badges and status cards
- Search/filter over recent history entries
- Output copy utility

The UI is intentionally simple and readable, prioritizing function over heavy client complexity.

---

## 18. Build and Runtime Operations

Build scripts:

- [scripts/build.ps1](scripts/build.ps1)
- [scripts/build.bat](scripts/build.bat)

Run scripts:

- [scripts/run.ps1](scripts/run.ps1)
- [scripts/run.bat](scripts/run.bat)

### 18.1 Build Workflow

- Ensure bin directory exists.
- Generate scanner via Flex.
- Generate parser via Bison.
- Compile C sources with GCC into solver binary.

### 18.2 Runtime Workflow

- Locate php.exe (XAMPP path candidates or PATH fallback).
- Start built-in PHP server at 127.0.0.1:8000 with web docroot.

### 18.3 Operational Coupling

Successful end-to-end operation requires:

- Solver binary present
- MySQL reachable
- PHP runtime available

Status pages provide visibility into these dependencies.

---

## 19. Performance Analysis and Complexity

### 19.1 Solver Complexity

Let $n$ be token count and AST size.

- Tokenization: $O(n)$
- Parsing: approximately $O(n)$ for LR parsing under this grammar
- Evaluation: $O(n)$
- Serialization: $O(n)$ over aggregate output size

### 19.2 Web Path Cost

Per solve request includes process startup overhead plus JSON decode and DB insert. For low-to-moderate traffic, this is acceptable. For high-throughput systems, consider persistent solver service instead of per-request process spawn.

### 19.3 Query Efficiency

History tables include key indexes that align with most retrieval patterns (user, created_at). This supports responsive dashboards for modest dataset sizes.

---

## 20. Security Analysis and Risk Review

Security policy exists in [SECURITY.md](SECURITY.md).

### 20.1 Positive Security Controls

- Password hashing/verification
- Prepared statements with PDO
- Session ID regeneration on login
- Role-checked access guards
- HTML escaping in output rendering
- Shell argument escaping for solver invocation

### 20.2 Notable Risks

1. Default seeded credentials are predictable if not changed.
2. CSRF protection tokens are not implemented on forms.
3. No rate limiting or lockout on login attempts.
4. No strict Content Security Policy headers.
5. history.user_id lacks explicit foreign key integrity constraints.

### 20.3 Priority Mitigation Plan

- Enforce credential reset on first run.
- Add CSRF tokens and validation.
- Add login throttling and audit logging.
- Add security headers (CSP, X-Frame-Options, etc.).
- Add DB-level constraints and migration scripts.

---

## 21. Reliability, Error Handling, and Recovery

### 21.1 Solver-Level Error Handling

- Parse errors produce structured messages and suggestions.
- Divide-by-zero handled explicitly.
- JSON fallback returned when result pointer is invalid.

### 21.2 Web-Level Error Handling

- Missing binary returns actionable guidance.
- Non-JSON solver output handled gracefully.
- DB connection fallback attempts multiple credential sets.

### 21.3 Recovery Perspective

The application has acceptable fault transparency for development/demo context. Productionization would require stronger telemetry, retry strategy, and health probes.

---

## 22. Testing Strategy and Validation Plan

### 22.1 Existing Test Inputs

Representative input corpus is available in [txt.txt](txt.txt), including chained operations and larger numeric cases.

### 22.2 Recommended Automated Test Layers

1. Unit tests (C evaluator and AST helpers)
2. Parser grammar acceptance/rejection tests
3. CLI contract tests for JSON schema compliance
4. PHP integration tests for auth and solver invocation
5. Database migration and schema integrity tests
6. UI smoke tests for login/dashboard/history flows

### 22.3 Example Functional Cases

- Valid: “sum of 5 and 3 multiplied by 2”
- Valid: “multiply 7 with 6 then add 2”
- Invalid: unexpected symbols or incomplete expressions
- Runtime error: division by zero sentence patterns

### 22.4 Non-Functional Tests

- Burst execution of repeated solver requests
- Concurrent user history inserts
- Credential brute-force simulation for lockout validation

---

## 23. Quality Findings and Defect Notes

### 23.1 Observed Logic Defect in Dashboard Counter Path

In [web/index.php](web/index.php), a statement chain assigns recentCount from the boolean return of execute in one branch before correcting later. Although later logic recalculates for non-admin users, this pattern is brittle and can cause maintenance confusion.

Recommendation:

- Remove intermediate boolean assignment path.
- Use explicit countStatement fetchColumn consistently.

### 23.2 Schema/Bootstrap Duplication Consideration

Schema is declared in SQL and partially managed in bootstrap logic. This is practical for compatibility but can drift over time.

Recommendation:

- Introduce versioned migration scripts and one source of truth.

### 23.3 Process Model Tradeoff

Solver invoked as external process for each solve. This is clear and modular but may become costly under load.

Recommendation:

- Future option: long-lived worker service or local daemon mode.

---

## 24. Improvement Roadmap

### 24.1 Language and Parser Enhancements

- Variables and assignment
- Parenthetical language hints in text form
- Unary operations and negative numbers handling rules
- Better natural-language disambiguation

### 24.2 Explainability Enhancements

- Rule-by-rule parse trace
- Highlighted precedence reasoning
- Error examples with corrected alternatives

### 24.3 Security and Governance

- Enforced password policy
- CSRF middleware
- Account lockout and anomaly detection
- Structured audit trail table

### 24.4 Scalability and Operations

- API endpoint mode with JSON over HTTP
- Caching for repeated identical expressions
- Structured logging and metrics export

---

## 25. Deployment Guidance

### 25.1 Local Deployment Steps

1. Install Flex, Bison, GCC, PHP, MySQL.
2. Run build script to generate solver binary.
3. Import SQL schema into database.
4. Configure DB credentials with environment variables if needed.
5. Run PHP server script.
6. Access app via browser and validate status page.

### 25.2 Production Considerations

- Replace built-in PHP server with managed web server stack.
- Harden MySQL credentials and network exposure.
- Disable seeded demo credentials.
- Add backup/restore policy for history and user tables.

---

## 26. Conclusion

MathLang Solver is a strong example of interdisciplinary engineering that combines compiler construction concepts with practical web application delivery. Its architecture is clear, modular, and pedagogically meaningful:

- Language parsing and AST interpretation are implemented in a deterministic native core.
- Web integration provides usability, persistence, and role-aware administration.
- The codebase is small enough to study end-to-end but rich enough to support meaningful extensions.

With targeted improvements in security hardening, migration discipline, and testing automation, this project can evolve from an educational prototype into a robust service-grade platform.

---

## 27. Appendix A: Key File Walkthrough

Core engine:

- [src/main.c](src/main.c): CLI input intake and solver orchestration
- [src/lexer.l](src/lexer.l): lexical tokenization rules
- [src/parser.y](src/parser.y): grammar and AST production
- [src/ast.h](src/ast.h): AST type model
- [src/ast.c](src/ast.c): AST creation/free and operator labels
- [src/evaluator.c](src/evaluator.c): evaluation, JSON output, parse state helpers
- [src/solver.h](src/solver.h): solver result contract

Web stack:

- [web/config.php](web/config.php): runtime configuration and binary path
- [web/db.php](web/db.php): PDO connection and fallback attempts
- [web/auth.php](web/auth.php): sessions, login, registration, guards
- [web/solver.php](web/solver.php): process bridge to native solver
- [web/index.php](web/index.php): main dashboard + solve workflow
- [web/history.php](web/history.php): role-aware history listing
- [web/admin.php](web/admin.php): grammar/user management
- [web/status.php](web/status.php): dependency and dataset health metrics

Operations and setup:

- [sql/schema.sql](sql/schema.sql): schema and seed grammar records
- [scripts/build.ps1](scripts/build.ps1): PowerShell build pipeline
- [scripts/run.ps1](scripts/run.ps1): PowerShell run server helper
- [scripts/build.bat](scripts/build.bat): CMD build pipeline
- [scripts/run.bat](scripts/run.bat): CMD run server helper

Policy and reference:

- [README.md](README.md): setup and feature documentation
- [SECURITY.md](SECURITY.md): vulnerability disclosure policy
- [txt.txt](txt.txt): sample expression test corpus

---

## 28. Appendix B: Representative Inputs and Outputs

### B.1 Sample Input Styles

- add 12 and 8
- divide 18 by 2
- multiply 7 with 6 then add 2
- sum of 100 and 250

### B.2 Expected Output Attributes

For successful solve:

- ok = true
- numeric result available
- tokens list not empty
- steps list captures operation reductions
- expression and tree are generated

For failed solve:

- ok = false
- error message present
- suggestion present
- partial metadata still usable for diagnostics

---

## 29. Appendix C: Suggested Documentation Pack for Submission

To convert this repository into a full formal submission package, include:

1. This analysis report (Markdown exported to PDF/Word).
2. Architecture diagram (component and sequence).
3. ER diagram for app_users, history, grammar_rules.
4. Test evidence document with screenshots and logs.
5. Security checklist and mitigation status matrix.
6. Maintenance guide with upgrade and migration steps.

If formatted with standard academic settings (A4, ~12pt, 1.5 line spacing, section breaks, diagrams), this report and appendices can be expanded to exceed 20 pages comfortably.

---

## 30. Functional Requirement Matrix

This section maps practical product behavior to implementation points. It is useful in academic review, QA validation, and stakeholder audits.

### 30.1 Authentication and Access Requirements

FR-01: User shall be able to register a new account with username, optional display name, and password.

- Input validation implemented in [web/auth.php](web/auth.php).
- Username policy enforced with regex and length constraints.
- Password minimum length check enforced before insert.

FR-02: User shall be able to log in with valid credentials.

- Credential verification handled through password_verify in [web/auth.php](web/auth.php).
- Login routing and request handling in [web/login.php](web/login.php).

FR-03: Session shall be regenerated after successful login to reduce fixation risk.

- Session regeneration implemented in login helper in [web/auth.php](web/auth.php).

FR-04: User shall be able to log out and terminate authenticated session state.

- Logout implementation in [web/logout.php](web/logout.php) with cookie invalidation.

FR-05: Admin pages shall be restricted to administrative role.

- Role guard function app_require_admin in [web/auth.php](web/auth.php).
- Applied to [web/admin.php](web/admin.php) and [web/status.php](web/status.php).

### 30.2 Solver and Explainability Requirements

FR-06: System shall accept sentence-style arithmetic text and attempt deterministic parse.

- Parsing grammar defined in [src/parser.y](src/parser.y).
- Tokenization map defined in [src/lexer.l](src/lexer.l).

FR-07: System shall produce numerical result for valid expressions.

- Recursive evaluator logic in [src/evaluator.c](src/evaluator.c).

FR-08: System shall provide parse trace metadata for educational transparency.

- Token recording via solver_record_token in [src/evaluator.c](src/evaluator.c).

FR-09: System shall provide step-by-step operation explanation.

- StepList collection and formatted step strings in [src/evaluator.c](src/evaluator.c).

FR-10: System shall provide tree representation of parsed structure.

- build_tree_string generator in [src/evaluator.c](src/evaluator.c).

FR-11: System shall provide machine-readable JSON output to enable integration.

- JSON serializer in [src/evaluator.c](src/evaluator.c).

### 30.3 Persistence and Administration Requirements

FR-12: System shall persist each solve event with input and JSON output.

- Insert path in [web/index.php](web/index.php).
- Schema support in [sql/schema.sql](sql/schema.sql).

FR-13: Standard user shall see own recent solve records only.

- User-scoped query in [web/history.php](web/history.php).

FR-14: Admin shall see global history with user attribution.

- Admin query with JOIN in [web/history.php](web/history.php).

FR-15: Admin shall manage grammar keyword metadata.

- Rule create/update/delete controls in [web/admin.php](web/admin.php).

FR-16: Admin shall manage user role and profile data.

- User create/update forms and handlers in [web/admin.php](web/admin.php).

### 30.4 Operational Requirements

FR-17: Project shall be buildable via script-based workflow on Windows.

- Build scripts in [scripts/build.ps1](scripts/build.ps1) and [scripts/build.bat](scripts/build.bat).

FR-18: Project shall run in local environment using bundled or installed PHP runtime.

- Runtime scripts in [scripts/run.ps1](scripts/run.ps1) and [scripts/run.bat](scripts/run.bat).

FR-19: Status page shall expose key dependency readiness and record counts.

- Status dashboard in [web/status.php](web/status.php).

FR-20: Project shall document setup and security reporting process.

- Setup docs in [README.md](README.md), vulnerability policy in [SECURITY.md](SECURITY.md).

---

## 31. Non-Functional Requirement Analysis

### 31.1 Usability

The project emphasizes low-friction usage for non-technical users:

- Login/register on single screen
- Natural sentence input instead of strict math notation
- Immediate result visualization
- Explainability panels (tokens, AST, steps)

Usability strengths:

- Supports both novice and advanced inspection modes.
- Error suggestions are human-readable.
- History supports workflow continuity.

Usability limitations:

- Language grammar is still finite and rigid.
- No live syntax hints while typing.
- No beginner tutorial overlay in UI.

### 31.2 Maintainability

Maintainability is moderate to good due to:

- clear folder separation
- small focused files
- explicit helper functions

Maintainability risks:

- Mixed schema ownership (SQL file + bootstrap mutation)
- generated parser artifacts under source tree may drift
- minimal automated tests currently in repository

### 31.3 Portability

Current project is Windows-oriented in scripts and binary naming.

Portability opportunities:

- produce Linux and macOS build scripts
- abstract solver binary path to platform-specific naming
- containerize Flex/Bison/GCC toolchain for deterministic builds

### 31.4 Security

Baseline controls are in place, but modern production security requires:

- CSRF controls
- brute-force resistance
- stronger default environment hardening
- explicit session cookie security attributes and TLS assumptions

### 31.5 Scalability

The current model is suitable for educational and light multi-user deployment.

Scaling constraints include:

- process spawn per solve request
- server-side rendering for every update
- no async queue or worker model

---

## 32. End-to-End Sequence Flows

### 32.1 Solve Request Sequence

1. Browser posts input text to dashboard endpoint.
2. [web/index.php](web/index.php) validates session and input.
3. [web/solver.php](web/solver.php) runs native executable with escaped arguments.
4. [src/main.c](src/main.c) receives argument string.
5. Lexer tokenizes text based on [src/lexer.l](src/lexer.l).
6. Parser applies grammar from [src/parser.y](src/parser.y) to build AST.
7. Evaluator traverses AST and computes result in [src/evaluator.c](src/evaluator.c).
8. JSON payload emitted to stdout.
9. PHP decodes JSON, inserts solve record into history table.
10. Dashboard renders result, steps, AST, and tokens.

### 32.2 Login Sequence

1. Guest submits username and password.
2. Login handler checks DB availability and bootstraps if needed.
3. User lookup performed via prepared statement.
4. Password hash verified.
5. Session ID regenerated and user snapshot stored in session.
6. Browser redirected to authenticated dashboard.

### 32.3 Admin Rule Update Sequence

1. Admin opens management page.
2. Form POST action identifies save-rule or delete-rule.
3. Server validates input.
4. Database mutation is executed via prepared statement.
5. Updated rule table is queried and rendered.

---

## 33. Data Model Deep Dive and ER Reasoning

### 33.1 Entities

Entity: app_users

- Primary key: id
- Business key: username (unique)
- Attributes: display_name, password_hash, role, timestamps

Entity: history

- Primary key: id
- Foreign association (logical): user_id -> app_users.id
- Attributes: input_text, output_json, result_value, created_at

Entity: grammar_rules

- Primary key: id
- Business key: keyword (unique)
- Attributes: operation, description, created_at

### 33.2 Cardinality and Access Pattern

- One user can create many history records.
- Grammar rules are shared global metadata managed by admins.
- History queries are split by role filtering strategy.

### 33.3 Normalization Notes

Schema is mostly normalized for current scope. output_json intentionally stores denormalized response payload for forensic and traceability value.

### 33.4 Indexing Review

Current indexes:

- history(user_id)
- history(created_at)

Potential additions:

- composite index history(user_id, created_at)
- optional role index in app_users if role filtering grows

---

## 34. Grammar Design Rationale and Coverage

Grammar design balances flexibility and determinism.

### 34.1 Supported Linguistic Shapes

- Prefix noun form: sum of A and B
- Verb-initial form: multiply A with B
- Infix operator form: A add B
- Chained action form: A then multiply B

### 34.2 Ambiguity Control

The grammar uses explicit connector tokens and precedence declarations to reduce ambiguity. Natural language remains inherently ambiguous, so constrained phrase templates are used as a controlled language approach.

### 34.3 Error Messaging Quality

Parser and lexer cooperate to provide error suggestions, which is stronger than generic parse failure messages and improves user learnability.

### 34.4 Coverage Expansion Strategy

Future grammar expansion should follow:

1. Add lexical aliases carefully.
2. Add parser productions with explicit precedence impact review.
3. Extend evaluator and operator enum if new operation introduced.
4. Add regression tests for acceptance and rejection cases.

---

## 35. Algorithmic Walkthrough with Example

Example input:

sum of 5 and 3 multiplied by 2

High-level transformation:

1. Tokens: SUM, OF, NUMBER(5), AND, NUMBER(3), MULTIPLY, BY, NUMBER(2)
2. Parse tree built according to grammar precedence.
3. AST formed with binary operator nodes and numeric leaves.
4. Evaluator computes subtree values recursively.
5. Steps appended as operations resolve.
6. JSON emitted with full diagnostic fields.

Evaluation math perspective:

If parsed as $(5 + (3 * 2))$, result is $11$.

The tree and step output make the chosen interpretation explicit and inspectable.

---

## 36. Error Taxonomy and UX Implications

### 36.1 Parse Errors

- Missing numeric operand
- Missing connector keyword
- Unknown symbol tokens

Current behavior:

- Error string populated
- Suggestion string populated
- Solve status false

### 36.2 Runtime Evaluation Errors

- Division by zero
- unsupported operator (defensive branch)

### 36.3 Integration Errors

- Missing solver binary
- solver output not valid JSON
- solver process launch failure

### 36.4 Data Layer Errors

- database unavailable
- credential mismatch

UX recommendation:

- Introduce persistent notification area with error code categories.
- Add copyable diagnostics for support workflows.

---

## 37. Security Threat Model (STRIDE-Oriented)

### 37.1 Spoofing

Risk:

- Weak credentials or unchanged seeded accounts can enable unauthorized access.

Controls:

- password hashing, role checks

Gaps:

- no mandatory reset for seeded users

### 37.2 Tampering

Risk:

- unauthorized mutation of grammar rules or role assignments

Controls:

- admin guard on management pages

Gaps:

- missing CSRF tokens can allow authenticated session abuse

### 37.3 Repudiation

Risk:

- lack of action-level audit tables

Recommendation:

- add immutable audit logs for admin actions and login events

### 37.4 Information Disclosure

Risk:

- diagnostic errors may reveal infrastructure details

Recommendation:

- separate user-safe and internal error verbosity levels

### 37.5 Denial of Service

Risk:

- repeated process spawning and expensive payloads

Recommendation:

- input size limits, request throttling, queue controls

### 37.6 Elevation of Privilege

Risk:

- insufficient admin action controls if session hijack occurs

Recommendation:

- stronger session security attributes and multi-factor option for admin

---

## 38. Comprehensive Test Matrix

This section can be used directly in QA annexes.

### 38.1 Parser Acceptance Tests

TC-P-001

- Input: add 5 and 3
- Expected: ok true, result 8

TC-P-002

- Input: difference between 10 and 4
- Expected: ok true, result 6

TC-P-003

- Input: multiply 7 with 6 then add 2
- Expected: ok true, deterministic chain result

TC-P-004

- Input: divide 10 by 0
- Expected: ok false, error indicates division by zero

TC-P-005

- Input: add @ and 2
- Expected: ok false, unexpected character guidance

### 38.2 Authentication Tests

TC-A-001

- Register valid account
- Expected: success redirect and login availability

TC-A-002

- Register duplicate username
- Expected: exists error path

TC-A-003

- Login valid seeded user
- Expected: session established

TC-A-004

- Login invalid password
- Expected: invalid error message

TC-A-005

- Access admin page as standard user
- Expected: redirect denied/login-required path

### 38.3 History and Persistence Tests

TC-H-001

- Submit successful solve
- Expected: history row created with JSON output

TC-H-002

- Standard user history view
- Expected: only own rows visible

TC-H-003

- Admin history view
- Expected: all users visible with attribution

### 38.4 Security Tests

TC-S-001

- SQL injection payload in login username
- Expected: no query break due to prepared statements

TC-S-002

- XSS payload in expression input
- Expected: escaped render in history/output views

TC-S-003

- CSRF simulation on admin action
- Expected current state likely vulnerable without token; mark as improvement-required

---

## 39. Operations, Monitoring, and Incident Handling

### 39.1 Operational Baseline

Before opening to users, operator should verify:

1. Database connectivity on status page
2. Solver binary detection
3. User and grammar tables present
4. Build scripts produce fresh binary

### 39.2 Suggested Monitoring Signals

- solver process failure count
- parse error rate
- average solve response time
- login failure rate
- admin action frequency

### 39.3 Incident Response Playbook

Scenario: MySQL outage

1. Check service availability.
2. Verify credentials in environment/config.
3. Validate DB presence and user grants.
4. Confirm app status page returns connected state.

Scenario: solver binary missing/corrupt

1. Re-run build script.
2. Validate bin output path.
3. Re-test solver CLI directly.

Scenario: suspicious admin changes

1. Review change timestamps and actor account.
2. Reset compromised passwords.
3. Restore grammar/user state from backup.

---

## 40. Refactoring and Modernization Recommendations

### 40.1 Architecture Refactor

- Introduce service layer in PHP to isolate DB and solver orchestration concerns.
- Introduce migration framework for schema evolution.
- Split monolithic dashboard file into partials/templates.

### 40.2 Parser Refactor

- Add grammar versioning notes and test snapshots.
- Introduce explicit unit tests for AST constructor and evaluator branch coverage.

### 40.3 Security Refactor

- Add CSRF token generation/verification to all POST forms.
- Add rate limiting for authentication endpoints.
- Add password complexity and rotation policy controls.

### 40.4 DevEx Improvements

- Add Makefile and cross-platform scripts.
- Add CI pipeline for lint, build, and smoke tests.
- Add static analysis stage for C and PHP.

---

## 41. Academic Evaluation Perspective

From an academic grading standpoint, this project scores strongly in:

- practical compiler construction usage
- interpretable AI-adjacent explanation workflow
- full-stack integration skills
- clean modular decomposition

Common evaluation dimensions and expected standing:

1. Problem formulation: strong
2. Method selection: strong
3. Implementation completeness: medium-strong
4. Validation and testing evidence: medium (can be improved)
5. Security and production hardening: medium (improvement opportunities)

To maximize evaluation score, add:

- formal test evidence tables with pass/fail logs
- ER and sequence diagrams
- performance benchmark appendix
- risk mitigation matrix with status and owners

---

## 42. Extended Conclusion and Readiness Assessment

MathLang Solver is a capable and coherent system that demonstrates both theoretical grounding and practical engineering. The design choice to pair a deterministic parser engine with a lightweight web platform is justified and effective for the project goals.

Readiness levels:

- Educational demonstration: ready
- Classroom laboratory usage: ready with minor setup guidance
- Small internal pilot deployment: feasible after CSRF and credential hardening
- Production-scale public deployment: requires additional security, test automation, and scalability investments

The codebase provides a strong base for future research and productization directions, including richer language support, formal verification of grammar behavior, and service-oriented scaling.

---

## 43. Submission Packaging Checklist

Use this checklist before final report submission.

1. Title page with institution formatting
2. Signed declaration (if required by institution)
3. Abstract and keywords
4. Problem statement and objectives
5. Literature/background section (optional but recommended)
6. Methodology and architecture
7. Implementation details with code references
8. Database design and normalization notes
9. Testing plan and empirical results
10. Security review and risk matrix
11. Discussion of limitations
12. Future work roadmap
13. Conclusion
14. References
15. Appendices with screenshots and logs

---

## 44. References and Standards Alignment (Suggested)

This project can be mapped to common software engineering and security standards for academic discussion.

- OWASP ASVS (web security controls baseline)
- STRIDE threat modeling approach
- ISO/IEC 25010 quality model concepts
- Compiler design workflow: lexical, syntax, semantic evaluation pipeline

For a formal thesis-style submission, include citation-backed discussion in a bibliography section and tie each recommendation to one or more standards.

---

## 45. Module-by-Module Implementation Commentary

This section provides implementation commentary similar to a code walkthrough chapter commonly required in long-form final year reports.

### 45.1 CLI Entrypoint Behavior

In [src/main.c](src/main.c), input collection supports two modes:

1. Argument-joined sentence input
2. Full stdin capture when no arguments are provided

This dual-mode behavior is useful for:

- manual terminal experimentation
- scripted input streams
- integration with process wrappers

The return contract is pragmatic:

- on successful solve or parse-failure-with-JSON, output is printed and exit remains non-fatal for caller observability
- usage errors return non-zero status

### 45.2 Parser State Handling

Parser state globals in [src/evaluator.c](src/evaluator.c) and declarations in [src/parser_state.h](src/parser_state.h) provide a simple bridge between generated parser artifacts and runtime helpers.

Strength:

- compact integration with Bison/Flex APIs

Risk:

- global state can complicate concurrent execution if reused inside threaded service model

Future adaptation for service mode would require parser-state encapsulation per request context.

### 45.3 Dynamic Buffering and String Builder Pattern

The evaluator includes dynamic string builders and append helpers to compose:

- AST text trees
- expression strings
- JSON payloads

This approach avoids fixed-size truncation risk in many places and is appropriate for variable-length user inputs. Memory growth strategy (doubling capacity) is computationally efficient for append-heavy workloads.

### 45.4 Token Capture and Observability

Token recording is implemented by storing token labels and optional lexemes in a growable array. This observability channel is excellent for both:

- educational explanation
- debugging failed parse attempts

It also makes the solver output self-describing for automated diagnostics.

### 45.5 StepList Data Structure

StepList in [src/evaluator.c](src/evaluator.c) is a small dynamic list abstraction used to capture operation reduction sequence. While minimal, it behaves similarly to a vector container and supports predictable iteration order.

Potential extension:

- include operand subtree hash or node IDs for richer provenance mapping.

### 45.6 PHP Bootstrap Strategy

The bootstrap routine in [web/auth.php](web/auth.php) handles table creation and lightweight migration checks at runtime. This is user-friendly for demos because setup friction is low.

However, in production-grade applications, schema drift and startup overhead are better handled with explicit migration tooling.

### 45.7 DB Connection Candidate Strategy

[web/db.php](web/db.php) attempts multiple credential/database combinations before giving up. This design favors resilience in varied local setups, especially classroom systems where credentials differ.

Tradeoff:

- harder to reason about expected connection target in constrained production environments.

Recommendation:

- keep fallback for development profile only, enforce strict single DSN in production profile.

### 45.8 Page-Level Security Guards

Protected pages call app_require_login or app_require_admin early, which is a good fail-fast pattern. This minimizes accidental data exposure due to rendering logic mistakes later in request handling.

### 45.9 Output Escaping Discipline

Most user-originating or DB-originating strings are escaped via htmlspecialchars before rendering. This is an important anti-XSS baseline and is consistently applied in key pages.

### 45.10 Scripted Toolchain Practicality

Build and run scripts provide low-friction adoption. For assessment reports, this is valuable evidence of reproducibility and deployment readiness.

---

## 46. Risk Register and Mitigation Tracking Template

This chapter can be copied directly into a governance appendix.

### 46.1 Risk Register

R-01: Default credentials left unchanged

- Impact: high
- Likelihood: medium-high in demos
- Mitigation: force password reset and disable seeded users after initialization
- Owner: application maintainer

R-02: Missing CSRF protection on state-changing forms

- Impact: high for authenticated admins
- Likelihood: medium
- Mitigation: per-session CSRF token middleware
- Owner: backend maintainer

R-03: Lack of login attempt throttling

- Impact: medium-high
- Likelihood: medium-high on internet-exposed deployments
- Mitigation: per-IP and per-account rate limits, temporary lockouts
- Owner: security lead

R-04: No explicit DB foreign key constraints for history.user_id

- Impact: medium
- Likelihood: medium
- Mitigation: add migration with FK and ON DELETE policy
- Owner: database maintainer

R-05: Per-request native process spawning overhead

- Impact: medium for high traffic
- Likelihood: medium under scale
- Mitigation: pooled worker service architecture
- Owner: platform engineer

R-06: No centralized structured logging

- Impact: medium incident response delay
- Likelihood: high over time
- Mitigation: add request IDs and structured logs for auth/admin/solver paths
- Owner: operations engineer

R-07: Runtime schema mutation may create uncertainty

- Impact: medium
- Likelihood: medium
- Mitigation: versioned migrations and startup precheck
- Owner: release manager

R-08: Insufficient automated regression suite

- Impact: medium-high for change safety
- Likelihood: high as grammar expands
- Mitigation: CI test matrix and parser snapshot tests
- Owner: QA lead

### 46.2 Mitigation Priority Groups

Priority A (immediate): R-01, R-02, R-03

Priority B (short-term): R-04, R-08

Priority C (medium-term): R-05, R-06, R-07

### 46.3 Completion Tracking Fields

For each risk, track:

- status (open, in progress, mitigated)
- completion date
- evidence link (commit, PR, screenshot, test result)
- reviewer sign-off

---

## 47. Verification Evidence Framework for Final Submission

Many institutions require evidence-oriented sections. This framework can be used directly.

### 47.1 Build Evidence

Capture:

- successful execution logs from build script
- generated artifacts in bin directory
- tool version snapshots (gcc, bison, flex, php, mysql)

### 47.2 Runtime Evidence

Capture:

- status dashboard screenshot showing DB connected and solver ready
- sample solve execution screenshot with AST and steps
- admin panel screenshot with rule and user management

### 47.3 Security Evidence

Capture:

- SQL injection negative tests
- XSS output-escaping validations
- role-gate redirection tests for unauthorized access

### 47.4 Data Evidence

Capture:

- history table rows before and after solve requests
- record attribution for user/admin views
- grammar rule create/update/delete snapshots

### 47.5 Regression Evidence

Capture:

- test corpus run results using expressions from [txt.txt](txt.txt)
- parse failure case outcomes and suggestion quality

---

## 48. Detailed Future Architecture Options

This section outlines evolution pathways with decision tradeoffs.

### 48.1 Option A: Keep Process-Per-Request Model

Pros:

- simplest operational model
- strong isolation per run
- easy crash containment

Cons:

- startup overhead on every request
- constrained throughput

Best fit:

- classroom usage
- low-volume internal tools

### 48.2 Option B: Local Persistent Solver Service

Concept:

- run parser engine as long-lived local service
- PHP communicates through local IPC/HTTP

Pros:

- reduced latency and higher throughput
- easier performance instrumentation

Cons:

- additional lifecycle and process supervision complexity

Best fit:

- moderate traffic deployment with tighter response targets

### 48.3 Option C: API-First Microservice Split

Concept:

- solver becomes separate API service
- web dashboard is standalone client app

Pros:

- independent scaling
- cleaner interface contracts
- easier language/runtime diversification

Cons:

- network security and distributed complexity
- higher infrastructure overhead

Best fit:

- multi-client ecosystem or integration-heavy roadmap

### 48.4 Recommended Evolution Path

Stage 1:

- harden current architecture (security + tests)

Stage 2:

- introduce internal service interface while preserving current UX

Stage 3:

- externalize as standalone API when ecosystem demand justifies it

---

## 49. Final Assessment Summary

Overall maturity assessment:

- Concept and architecture: strong
- Core implementation quality: strong for scope
- Security baseline: moderate with clear upgrade path
- Testing maturity: moderate-low currently, high potential
- Documentation quality: good and now report-ready

This project is a credible demonstration of applying compiler concepts in a practical full-stack product. With the improvements listed in this report, it can transition from prototype-quality deployment toward robust operational quality.
