import urllib.request
import json
import ssl

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

# Run ID for the last failed run
run_id = 27394221331

try:
    # First get the job ID
    req = urllib.request.Request(
        f"https://api.github.com/repos/ChoksiecutieXDD/bigfunv3-laravel/actions/runs/{run_id}/jobs",
        headers={"User-Agent": "Mozilla/5.0"}
    )
    with urllib.request.urlopen(req, context=ctx) as response:
        data = json.loads(response.read().decode())
        job_id = data["jobs"][0]["id"]
        print(f"Job ID: {job_id}")
        
    # Now get the logs (need to follow redirect)
    logs_req = urllib.request.Request(
        f"https://api.github.com/repos/ChoksiecutieXDD/bigfunv3-laravel/actions/jobs/{job_id}/logs",
        headers={"User-Agent": "Mozilla/5.0"}
    )
    # Since it redirects to S3, urlopen will follow redirects automatically
    with urllib.request.urlopen(logs_req, context=ctx) as logs_response:
        logs_text = logs_response.read().decode('utf-8')
        # Print the last 100 lines of the logs
        lines = logs_text.split('\n')
        print("--- LAST 100 LINES OF LOGS ---")
        for line in lines[-100:]:
            print(line)
except Exception as e:
    print(f"Error: {e}")
