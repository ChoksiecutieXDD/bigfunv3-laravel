import urllib.request
import json
import ssl
import time

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

req = urllib.request.Request(
    "https://api.github.com/repos/ChoksiecutieXDD/bigfunv3-laravel/actions/runs?per_page=1",
    headers={"User-Agent": "Mozilla/5.0"}
)

try:
    with urllib.request.urlopen(req, context=ctx) as response:
        data = json.loads(response.read().decode())
        run = data["workflow_runs"][0]
        print(f"Latest Run ID: {run['id']}")
        print(f"Message: {run['head_commit']['message']}")
        print(f"Status: {run['status']}")
        print(f"Conclusion: {run['conclusion']}")
        print(f"Created At: {run['created_at']}")
except Exception as e:
    print(f"Error: {e}")
