# MathLang Solver

MathLang Solver is a compiler-inspired natural language math solver that combines a C parsing engine (Flex/Bison) with a PHP and MySQL web application for authenticated, browser-based use.

## Overview

This project accepts English-like arithmetic expressions, transforms them into an abstract syntax tree (AST), evaluates the result, and provides step-by-step explanation output. The web layer supports user authentication, solve history tracking, and administrative management tools.

## Key Features

- Natural language math parsing for common arithmetic phrasing
- AST generation and deterministic evaluation in C
- Step-by-step explanation generation
- Authenticated PHP dashboard for interactive solving
- User-specific solve history persisted in MySQL
- Administrative panel for keyword metadata and user access management

## Supported Examples

- `sum of 5 and 3 multiplied by 2`
- `difference between 10 and 4`
- `multiply 7 with 6 then add 2`
- `divide 10 by 2`
- `add 5 and 3`

## Technology Stack

- C (solver core)
- Flex and Bison (lexer/parser generation)
- PHP 8+ (web application)
- MySQL 8+ (data storage)
- PowerShell/BAT scripts for Windows build and run workflows

## Project Structure

- `src/`: C solver core and parser components
- `web/`: PHP application, authentication, and dashboard modules
- `sql/schema.sql`: database schema and initial seed data
- `scripts/`: Windows build and run scripts
- `bin/`: compiled solver executable output

## Requirements

- GCC (or MinGW GCC)
- Flex
- Bison
- PHP 8+
- MySQL 8+

If `php` is not available on PATH, use a bundled environment such as XAMPP.

## Build

Windows PowerShell:

```powershell
.\scripts\build.ps1
```

Windows Command Prompt:

```bat
scripts\build.bat
```

Expected binary output:

- `bin/mathlang_solver.exe`

## Database Setup

1. Create/import schema using:

```sql
source sql/schema.sql;
```

2. Default database target in `web/config.php` is `mathsolver_db`.
3. Optional environment variable overrides:

- `MATHSOLVER_DB_HOST`
- `MATHSOLVER_DB_PORT`
- `MATHSOLVER_DB_NAME`
- `MATHSOLVER_DB_USER`
- `MATHSOLVER_DB_PASS`

## Run the Application

1. Start MySQL (for example via XAMPP Control Panel).
2. Import `sql/schema.sql` into `mathsolver_db`.
3. Build the solver:

```powershell
.\scripts\build.ps1
```

4. Start the PHP server:

```powershell
.\scripts\run.ps1
```

5. Open `http://127.0.0.1:8000/` in a browser.
6. Seeded demo credentials:

- Admin: `admin` / `admin123`
- User: `user` / `user123`

## How It Works

1. Flex tokenizes incoming text.
2. Bison applies grammar rules and builds the AST.
3. The evaluator computes the numeric result from the AST.
4. Explanation routines produce readable solving steps.
5. The PHP layer stores and renders results from MySQL.

## Security

Please review [SECURITY.md](SECURITY.md) for vulnerability reporting and supported security update guidance.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Developer

- **Name:** Bornil Mahmud
- **Email:** bornilprof@gmail.com

## Roadmap

- Variable support and assignment parsing
- Multi-step natural language reasoning
- Dynamic grammar updates from managed rules
- Expanded role and permission controls
