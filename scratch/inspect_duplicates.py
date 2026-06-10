import pandas as pd
from collections import defaultdict

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
                    if name:
                        farms_by_name[name].append((idx, row[3], row[5]))
        except:
            pass
            
    print("Duplicate names details:")
    for name, items in farms_by_name.items():
        if len(items) > 1:
            print(f"Name: '{name}'")
            for idx, stir, size in items:
                print(f"  Row {idx}: STIR={stir}, Size={size}")
                
except Exception as e:
    print("Error:", e)
