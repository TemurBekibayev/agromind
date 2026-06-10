import pandas as pd
import sys

if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8')

file_path = "d:/projects/agromind/backend/resources/source/6-Х (фермер хужалиги)2026+.xls"
try:
    xl = pd.ExcelFile(file_path)
    df = xl.parse('туман Руйхат 11 (2)', header=None)
    
    potential_farms_no_stir = []
    
    for idx, row in df.iterrows():
        val0 = row[0]
        try:
            if pd.notnull(val0):
                f = float(val0)
                if f.is_integer() and f > 0:
                    name = str(row[1]).strip() if pd.notnull(row[1]) else ""
                    stir = row[3] if pd.notnull(row[3]) else None
                    if not stir:
                        potential_farms_no_stir.append((idx, name, row[2], row[5]))
        except:
            pass
            
    print(f"Found {len(potential_farms_no_stir)} rows with index but no STIR:")
    for idx, name, col2, size in potential_farms_no_stir:
        print(f"  Row {idx}: Name='{name}', Col2={col2}, Size={size}")
        
except Exception as e:
    print("Error:", e)
