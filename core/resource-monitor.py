import time
import psutil
import pandas as pd
from datetime import datetime

OUTPUT_FILE = "log_cpu_goldeneye+normal.csv" # jangan lupa ubah nama file sesuai skenario (normal,flashcrowd, serangan)
DURATION = 360  # 6 menit, lama pengambilan data lebih banyak 1 menit dari pengujian (5 menit)

data_log = []

print(f"Monitoring resource selama {DURATION} detik...")

start_time = time.time()
sample_no = 1

try:

    while time.time() - start_time < DURATION:

        current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        cpu_percent = psutil.cpu_percent(interval=1)

        ram_info = psutil.virtual_memory()

        ram_percent = ram_info.percent
        ram_used_mb = ram_info.used / (1024 ** 2)

        data_log.append({
            "Sample": sample_no,
            "Timestamp": current_time,
            "CPU_Usage(%)": cpu_percent,
            "RAM_Usage(%)": ram_percent,
            "RAM_Used(MB)": round(ram_used_mb, 2)
        })

        sample_no += 1

finally:

    df = pd.DataFrame(data_log)

    df.to_csv(
        OUTPUT_FILE,
        index=False
    )

    print(f"Data tersimpan pada {OUTPUT_FILE}")
