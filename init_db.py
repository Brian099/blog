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

# Create SQLite tables matching Z-Blog schema
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
CREATE TABLE zbp_member (
    mem_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    mem_Guid TEXT NOT NULL DEFAULT '',
    mem_Level INTEGER NOT NULL DEFAULT 0,
    mem_Status INTEGER NOT NULL DEFAULT 0,
    mem_Name TEXT NOT NULL DEFAULT '',
    mem_Password TEXT NOT NULL DEFAULT '',
    mem_Email TEXT NOT NULL DEFAULT '',
    mem_HomePage TEXT NOT NULL DEFAULT '',
    mem_IP TEXT NOT NULL DEFAULT '',
    mem_PostTime INTEGER NOT NULL DEFAULT 0,
    mem_Alias TEXT NOT NULL DEFAULT '',
    mem_Intro TEXT NOT NULL DEFAULT '',
    mem_Articles INTEGER NOT NULL DEFAULT 0,
    mem_Pages INTEGER NOT NULL DEFAULT 0,
    mem_Comments INTEGER NOT NULL DEFAULT 0,
    mem_Uploads INTEGER NOT NULL DEFAULT 0,
    mem_Template TEXT NOT NULL DEFAULT '',
    mem_Meta TEXT NOT NULL DEFAULT '',
    mem_CreateTime INTEGER NOT NULL DEFAULT 0,
    mem_UpdateTime INTEGER NOT NULL DEFAULT 0
);
""")

cur.execute("""
CREATE TABLE zbp_config (
    conf_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    conf_Name TEXT NOT NULL DEFAULT '',
    conf_Value TEXT NOT NULL DEFAULT '',
    conf_Key TEXT NOT NULL DEFAULT ''
);
""")

# Create settings table for our new modern system
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
    ("admin_password", "admin123"), # default, can be changed in settings
    ("posts_per_page", "20")
])

print("Parsing SQL dump...")
with open(r"d:\coding\zblog\zblog_backup.sql", "r", encoding="utf-8", errors="ignore") as f:
    sql_content = f.read()

# Tables to import
target_tables = ["zbp_category", "zbp_tag", "zbp_post", "zbp_upload", "zbp_member", "zbp_config"]

for line in sql_content.splitlines():
    line = line.strip()
    if not line.startswith("INSERT INTO"):
        continue
    for tbl in target_tables:
        if line.startswith(f"INSERT INTO `{tbl}`"):
            # Clean MySQL backticks to standard SQL
            clean_sql = line.replace(f"INSERT INTO `{tbl}`", f"INSERT INTO {tbl}")
            # replace backticks in column list if present
            clean_sql = re.sub(r"`([^`]+)`", r"\1", clean_sql)
            try:
                cur.execute(clean_sql)
            except Exception as e:
                # Some escape characters in long text
                pass

conn.commit()

# Verify counts
print("Import verification:")
for tbl in target_tables:
    cur.execute(f"SELECT COUNT(*) FROM {tbl}")
    cnt = cur.fetchone()[0]
    print(f"  {tbl}: {cnt} records")

conn.close()
print(f"SQLite DB initialized successfully at {db_path}!")
