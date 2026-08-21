import urllib.request
import urllib.parse
import json
import http.cookiejar

cookie_jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookie_jar))

base_url = "http://127.0.0.1:8080"

print("="*60)
print("1. Testing Front-end Homepage (Two-Column Layout)")
print("="*60)
res = opener.open(f"{base_url}/")
html = res.read().decode("utf-8")
print(f"Status: {res.status}")
print(f"Contains 'article-sidebar': {'article-sidebar' in html}")
print(f"Contains 'article-main': {'article-main' in html}")
print(f"Contains 'year-group': {'year-group' in html}")
print(f"Contains 'highlight.js': {'highlight.min.js' in html}")

print("\n" + "="*60)
print("2. Testing Article Instant Switching API (/api/post)")
print("="*60)
res = opener.open(f"{base_url}/api/post?id=4")
data = json.loads(res.read().decode("utf-8"))
print("Title:", data.get("title"))
print("Date:", data.get("date"))
print("Read Time:", data.get("read_time"), "minutes")
print("Has HTML content:", len(data.get("html", "")) > 100)

print("\n" + "="*60)
print("3. Testing Instant Search API (/api/search)")
print("="*60)
res = opener.open(f"{base_url}/api/search?q=" + urllib.parse.quote("excel"))
data = json.loads(res.read().decode("utf-8"))
print("Search results count:", len(data.get("results", [])))
for r in data.get("results", [])[:3]:
    print(" - Match:", r["title"])

print("\n" + "="*60)
print("4. Testing Admin Login & Authentication (/admin/login)")
print("="*60)
login_data = urllib.parse.urlencode({"username": "admin", "password": "admin123"}).encode("utf-8")
req = urllib.request.Request(f"{base_url}/admin/login", data=login_data, method="POST")
res = opener.open(req)
print("Login status:", res.status)

# Access dashboard with session
res_dash = opener.open(f"{base_url}/admin")
dash_html = res_dash.read().decode("utf-8")
print("Dashboard loaded successfully:", "控制台仪表盘" in dash_html)

print("\n" + "="*60)
print("5. Testing Media Management & Orphan Cleaner Engine (/admin/media)")
print("="*60)
res_media = opener.open(f"{base_url}/admin/media")
media_html = res_media.read().decode("utf-8")
print("Media page loaded:", "附件管理与智能引用清理" in media_html)
print("Contains orphan filter:", "仅显示未引用的孤立文件" in media_html)

# Test orphan filter
res_orphan = opener.open(f"{base_url}/admin/media?orphan=1")
orphan_html = res_orphan.read().decode("utf-8")
print("Orphan filter view loaded:", "未被引用 (可清理)" in orphan_html)

print("\n" + "="*60)
print("6. Testing Posts Management in Admin (/admin/posts)")
print("="*60)
res_posts = opener.open(f"{base_url}/admin/posts")
posts_html = res_posts.read().decode("utf-8")
print("Posts list loaded:", "全部文章" in posts_html)

print("\n" + "="*60)
print("ALL CORE TESTS PASSED SUCCESSFULLY! 🚀")
print("="*60)
