import pandas as pd
import sys

if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8')

file_path = "d:/projects/agromind/backend/resources/source/6-Х (фермер хужалиги)2026+.xls"
try:
    xl = pd.ExcelFile(file_path)
    print("Sheet names:", xl.sheet_names)
    for name in xl.sheet_names:
        df = xl.parse(name, header=None)
        non_empty = df.dropna(how='all')
        print(f"Sheet '{name}': shape={df.shape}, non-empty rows={len(non_empty)}")
        if len(non_empty) > 0:
            print(f"Sample from '{name}':")
            # print first 5 populated rows
            for idx, row in non_empty.head(15).iterrows():
                vals = {col: val for col, val in row.items() if pd.notnull(val)}
                print(f"  Row {idx}: {vals}")
except Exception as e:
    print("Error:", e)
