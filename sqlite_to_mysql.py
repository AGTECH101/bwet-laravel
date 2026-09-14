#!/usr/bin/env python3
"""
sqlite_to_mysql.py

Converts a SQLite .sqlite/.db file into a MySQL-compatible .sql dump
(schema + data) that you can import via phpMyAdmin.

USAGE:
    python sqlite_to_mysql.py database.sqlite
    python sqlite_to_mysql.py database.sqlite output.sql
    python sqlite_to_mysql.py database.sqlite output.sql --data-only

Notes:
- Since this is a Laravel project, the cleanest path is usually:
    1. Point Laravel's .env at your empty MySQL database
    2. Run `php artisan migrate` to create the schema properly (correct
       indexes, foreign keys, column types, etc.)
    3. Run this script with --data-only and import just the INSERT
       statements into that already-migrated database.
  Use the full (schema + data) mode only if you don't want to re-run
  migrations and just want a best-effort standalone .sql file.
"""

import sqlite3
import sys
import re

SQLITE_TO_MYSQL_TYPE_MAP = {
    "INTEGER": "INT",
    "INT": "INT",
    "TINYINT": "TINYINT",
    "SMALLINT": "SMALLINT",
    "MEDIUMINT": "MEDIUMINT",
    "BIGINT": "BIGINT",
    "UNSIGNED BIG INT": "BIGINT UNSIGNED",
    "INT2": "SMALLINT",
    "INT8": "BIGINT",
    "TEXT": "TEXT",
    "CLOB": "TEXT",
    "VARCHAR": "VARCHAR(255)",
    "CHARACTER": "CHAR",
    "NCHAR": "CHAR",
    "NVARCHAR": "VARCHAR(255)",
    "BLOB": "BLOB",
    "REAL": "DOUBLE",
    "DOUBLE": "DOUBLE",
    "DOUBLE PRECISION": "DOUBLE",
    "FLOAT": "FLOAT",
    "NUMERIC": "DECIMAL(20,4)",
    "DECIMAL": "DECIMAL(20,4)",
    "BOOLEAN": "TINYINT(1)",
    "DATE": "DATE",
    "DATETIME": "DATETIME",
    "TIMESTAMP": "TIMESTAMP NULL",
}


def map_type(sqlite_type):
    if not sqlite_type:
        return "TEXT"
    t = sqlite_type.strip().upper()
    # strip length modifiers like VARCHAR(100) for lookup, but keep for VARCHAR/CHAR
    base = re.sub(r"\(.*\)", "", t).strip()
    if base.startswith("VARCHAR") or base.startswith("CHARACTER VARYING"):
        m = re.search(r"\((\d+)\)", t)
        length = m.group(1) if m else "255"
        return f"VARCHAR({length})"
    if base.startswith("CHAR"):
        m = re.search(r"\((\d+)\)", t)
        length = m.group(1) if m else "255"
        return f"CHAR({length})"
    return SQLITE_TO_MYSQL_TYPE_MAP.get(base, "TEXT")


def escape_identifier(name):
    return f"`{name}`"


def escape_value(value):
    if value is None:
        return "NULL"
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        return repr(value)
    if isinstance(value, bytes):
        return "0x" + value.hex()
    # string: escape backslashes, quotes, and control chars
    s = str(value)
    s = s.replace("\\", "\\\\")
    s = s.replace("'", "\\'")
    s = s.replace("\r", "\\r")
    s = s.replace("\n", "\\n")
    s = s.replace("\x00", "")
    return f"'{s}'"


def get_tables(cur):
    cur.execute("""
        SELECT name, sql FROM sqlite_master
        WHERE type='table' AND name NOT LIKE 'sqlite_%'
        ORDER BY name;
    """)
    return cur.fetchall()


def get_columns(cur, table):
    cur.execute(f"PRAGMA table_info({escape_identifier_sqlite(table)});")
    return cur.fetchall()  # cid, name, type, notnull, dflt_value, pk


def escape_identifier_sqlite(name):
    return f'"{name}"'


def build_create_table(cur, table_name):
    columns = get_columns(cur, table_name)
    col_defs = []
    primary_keys = []

    for cid, name, col_type, notnull, dflt_value, pk in columns:
        mysql_type = map_type(col_type)
        parts = [escape_identifier(name), mysql_type]

        is_single_int_pk = (
            pk == 1
            and (col_type or "").upper().startswith("INT")
        )
        if is_single_int_pk:
            parts.append("NOT NULL AUTO_INCREMENT")
        else:
            parts.append("NOT NULL" if notnull else "NULL")
            if dflt_value is not None:
                # avoid quoting for numeric-looking defaults, else quote it
                dv = dflt_value.strip("'\"")
                if re.match(r"^-?\d+(\.\d+)?$", dv):
                    parts.append(f"DEFAULT {dv}")
                elif dv.upper() in ("CURRENT_TIMESTAMP", "NULL"):
                    parts.append(f"DEFAULT {dv.upper()}")
                else:
                    parts.append(f"DEFAULT {escape_value(dv)}")

        col_defs.append(" ".join(parts))
        if pk:
            primary_keys.append(escape_identifier(name))

    stmt = f"DROP TABLE IF EXISTS {escape_identifier(table_name)};\n"
    stmt += f"CREATE TABLE {escape_identifier(table_name)} (\n  "
    stmt += ",\n  ".join(col_defs)
    if primary_keys:
        stmt += f",\n  PRIMARY KEY ({', '.join(primary_keys)})"
    stmt += "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n"
    return stmt


def build_inserts(cur, table_name, batch_size=200):
    cur.execute(f"SELECT * FROM {escape_identifier_sqlite(table_name)};")
    columns = [desc[0] for desc in cur.description]
    col_list = ", ".join(escape_identifier(c) for c in columns)

    rows = cur.fetchmany(batch_size)
    statements = []
    while rows:
        value_rows = []
        for row in rows:
            values = ", ".join(escape_value(v) for v in row)
            value_rows.append(f"({values})")
        stmt = (
            f"INSERT INTO {escape_identifier(table_name)} ({col_list}) VALUES\n"
            + ",\n".join(value_rows) + ";\n"
        )
        statements.append(stmt)
        rows = cur.fetchmany(batch_size)
    return statements


def main():
    if len(sys.argv) < 2:
        print("Usage: python sqlite_to_mysql.py <database.sqlite> [output.sql] [--data-only]")
        sys.exit(1)

    sqlite_path = sys.argv[1]
    args = sys.argv[2:]
    data_only = "--data-only" in args
    args = [a for a in args if a != "--data-only"]
    output_path = args[0] if args else "database_mysql.sql"

    conn = sqlite3.connect(sqlite_path)
    cur = conn.cursor()

    tables = get_tables(cur)
    if not tables:
        print("No tables found in the SQLite database.")
        sys.exit(1)

    with open(output_path, "w", encoding="utf-8") as out:
        out.write("SET FOREIGN_KEY_CHECKS=0;\n")
        out.write("SET NAMES utf8mb4;\n\n")

        for table_name, _ in tables:
            out.write(f"-- ----------------------------\n-- Table: {table_name}\n-- ----------------------------\n")
            if not data_only:
                out.write(build_create_table(cur, table_name))
                out.write("\n")

            insert_statements = build_inserts(cur, table_name)
            if insert_statements:
                out.write("\n".join(insert_statements))
                out.write("\n")
            out.write("\n")

        out.write("SET FOREIGN_KEY_CHECKS=1;\n")

    conn.close()
    mode = "data only" if data_only else "schema + data"
    print(f"Done ({mode}). Wrote {len(tables)} tables to '{output_path}'.")


if __name__ == "__main__":
    main()