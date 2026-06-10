import pandas as pd
import sys

if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8')

file_path = "d:/projects/agromind/backend/resources/source/6-Х (фермер хужалиги)2026+.xls"
try:
    xl = pd.ExcelFile(file_path)
    df = xl.parse('туман Руйхат 11 (2)', header=None)
    for r in [27, 28, 29, 30]:
        row = df.iloc[r]
        vals = {col: row[col] for col in range(len(row)) if pd.notnull(row[col])}
        print(f"Row {r}: {vals}")
except Exception as e:
    print("Error:", e)
