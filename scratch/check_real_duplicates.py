import pandas as pd
import sys
from collections import defaultdict

if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8')

file_path = "d:/projects/agromind/backend/resources/source/6-Х (фермер хужалиги)2026+.xls"
try:
    xl = pd.ExcelFile(file_path)
    df = xl.parse('туман Руйхат 11 (2)', header=None)
    
    farms_by_name = defaultdict(list)
    
    for idx, row in df.iterrows():
        val0 = row[0]
        try:
            if pd.notnull(val0):
                f = float(val0)
                if f.is_integer() and f > 0:
                    name = str(row[1]).strip() if pd.notnull(row[1]) else None
                    stir = row[3] if pd.notnull(row[3]) else None
                    if name and stir and "Жами массив" not in name:
                        farms_by_name[name].append((idx, stir, row[5]))
        except:
            pass
            
    duplicates = {name: items for name, items in farms_by_name.items() if len(items) > 1}
    print(f"Genuine duplicate names found: {len(duplicates)}")
    for name, items in duplicates.items():
        print(f"Name: '{name}'")
        for idx, stir, size in items:
            print(f"  Row {idx}: STIR={stir}, Size={size}")
            
except Exception as e:
    print("Error:", e)
