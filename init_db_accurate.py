import re
import sqlite3
import os

db_dir = r"d:\coding\zblog\data"
os.makedirs(db_dir, exist_ok=True)
db_path = os.path.join(db_dir, "blog.db")

if os.path.exists(db_path):
    os.remove(db_path)

conn = sqlite3.connect(db_path)
cur = conn.cursor()

# Tables schema
cur.execute("""
CREATE TABLE zbp_category (
    cate_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    cate_Name TEXT NOT NULL DEFAULT '',
    cate_Order INTEGER NOT NULL DEFAULT 0,
    cate_Type INTEGER NOT NULL DEFAULT 0,
    cate_Count INTEGER NOT NULL DEFAULT 0,
    cate_Alias TEXT NOT NULL DEFAULT '',
    cate_Intro TEXT NOT NULL DEFAULT '',
    cate_RootID INTEGER NOT NULL DEFAULT 0,
    cate_ParentID INTEGER NOT NULL DEFAULT 0,
    cate_Template TEXT NOT NULL DEFAULT '',
    cate_LogTemplate TEXT NOT NULL DEFAULT '',
    cate_Meta TEXT NOT NULL DEFAULT '',
    cate_Group TEXT NOT NULL DEFAULT '',
    cate_CreateTime INTEGER NOT NULL DEFAULT 0,
    cate_UpdateTime INTEGER NOT NULL DEFAULT 0,
    cate_PostTime INTEGER NOT NULL DEFAULT 0
);
""")

cur.execute("""
CREATE TABLE zbp_tag (
    tag_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    tag_Name TEXT NOT NULL DEFAULT '',
    tag_Order INTEGER NOT NULL DEFAULT 0,
    tag_Type INTEGER NOT NULL DEFAULT 0,
    tag_Count INTEGER NOT NULL DEFAULT 0,
    tag_Alias TEXT NOT NULL DEFAULT '',
    tag_Intro TEXT NOT NULL DEFAULT '',
    tag_Template TEXT NOT NULL DEFAULT '',
    tag_Meta TEXT NOT NULL DEFAULT '',
    tag_Group TEXT NOT NULL DEFAULT '',
    tag_CreateTime INTEGER NOT NULL DEFAULT 0,
    tag_UpdateTime INTEGER NOT NULL DEFAULT 0,
    tag_PostTime INTEGER NOT NULL DEFAULT 0
);
""")

cur.execute("""
CREATE TABLE zbp_post (
    log_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    log_CateID INTEGER NOT NULL DEFAULT 0,
    log_AuthorID INTEGER NOT NULL DEFAULT 0,
    log_Tag TEXT NOT NULL DEFAULT '',
    log_Status INTEGER NOT NULL DEFAULT 0,
    log_Type INTEGER NOT NULL DEFAULT 0,
    log_Alias TEXT NOT NULL DEFAULT '',
    log_IsTop INTEGER NOT NULL DEFAULT 0,
    log_IsLock INTEGER NOT NULL DEFAULT 0,
    log_Title TEXT NOT NULL DEFAULT '',
    log_Intro TEXT NOT NULL DEFAULT '',
    log_Content TEXT NOT NULL DEFAULT '',
    log_PostTime INTEGER NOT NULL DEFAULT 0,
    log_CommNums INTEGER NOT NULL DEFAULT 0,
    log_ViewNums INTEGER NOT NULL DEFAULT 0,
    log_Template TEXT NOT NULL DEFAULT '',
    log_Meta TEXT NOT NULL DEFAULT '',
    log_CreateTime INTEGER NOT NULL DEFAULT 0,
    log_UpdateTime INTEGER NOT NULL DEFAULT 0
);
""")

cur.execute("""
CREATE TABLE zbp_upload (
    ul_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    ul_AuthorID INTEGER NOT NULL DEFAULT 0,
    ul_Size INTEGER NOT NULL DEFAULT 0,
    ul_Name TEXT NOT NULL DEFAULT '',
    ul_SourceName TEXT NOT NULL DEFAULT '',
    ul_MimeType TEXT NOT NULL DEFAULT '',
    ul_PostTime INTEGER NOT NULL DEFAULT 0,
    ul_DownNums INTEGER NOT NULL DEFAULT 0,
    ul_LogID INTEGER NOT NULL DEFAULT 0,
    ul_Intro TEXT NOT NULL DEFAULT '',
    ul_Meta TEXT NOT NULL DEFAULT ''
);
""")

cur.execute("""
CREATE TABLE sys_setting (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);
""")

cur.executemany("INSERT INTO sys_setting (key, value) VALUES (?, ?)", [
    ("site_name", "技术思维棱镜"),
    ("site_subtitle", "专注技术记录、脚本折腾与实战经验分享"),
    ("author_name", "Brian"),
    ("author_bio", "热爱折腾 NAS、自动化脚本、数据处理与全栈开发的极客。"),
    ("admin_username", "admin"),
    ("admin_password", "admin123")
])

print("Reading sql file with exact utf-8...")
with open(r"d:\coding\zblog\zblog_backup.sql", "rb") as f:
    raw = f.read()

# Decode utf-8
sql_text = raw.decode("utf-8", errors="replace")

# Match all INSERT statements across multiple lines
target_tables = ["zbp_category", "zbp_tag", "zbp_post", "zbp_upload"]

for tbl in target_tables:
    # Pattern to find INSERT INTO `table` ... VALUES (...);
    pattern = re.compile(rf"INSERT INTO `{tbl}`\s*(\([^)]+\))?\s*VALUES\s*\((.*?)\);", re.DOTALL)
    matches = pattern.finditer(sql_text)
    count = 0
    for m in matches:
        cols_part = m.group(1) or ""
        vals_part = m.group(2)
        
        # Clean MySQL backticks
        clean_cols = cols_part.replace("`", "")
        stmt = f"INSERT INTO {tbl} {clean_cols} VALUES ({vals_part})"
        try:
            cur.execute(stmt)
            count += 1
        except Exception as e:
            pass
    print(f"Imported {tbl}: {count} rows")

conn.commit()

# Test check
cur.execute("SELECT log_ID, log_Title, log_PostTime FROM zbp_post WHERE log_Status=0 ORDER BY log_PostTime DESC LIMIT 5")
for r in cur.fetchall():
    print(f"  Post ID {r[0]}: {r[1]} (Time: {r[2]})")

conn.close()
print("Done!")
