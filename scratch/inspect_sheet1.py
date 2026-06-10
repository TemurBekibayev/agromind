import pandas as pd
import sys

if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8')

file_path = "d:/projects/agromind/backend/resources/source/6-Х (фермер хужалиги)2026+.xls"
try:
    xl = pd.ExcelFile(file_path)
    for sheet_name in xl.sheet_names:
        df = xl.parse(sheet_name, header=None)
        print(f"\n--- Sheet: {sheet_name} (shape: {df.shape}) ---")
        
        # Find rows where column 0 is an integer
        data_rows = []
        for idx, row in df.iterrows():
            val0 = row[0]
            try:
                if pd.notnull(val0):
                    f = float(val0)
                    if f.is_integer() and f > 0:
                        data_rows.append((idx, row))
            except:
                pass
                
        print(f"Total data rows found: {len(data_rows)}")
        print("Sample data rows (first 10):")
        for idx, r in data_rows[:10]:
            # print first 6 columns if they exist
            vals = {col: r[col] for col in range(min(df.shape[1], 6)) if pd.notnull(r[col])}
            print(f"  Row {idx}: {vals}")
except Exception as e:
    print("Error:", e)
