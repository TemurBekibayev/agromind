import pandas as pd
import json
import os

file_path = "d:/projects/agromind/backend/resources/source/6-Х (фермер хужалиги)2026+.xls"
output_dir = "d:/projects/agromind/backend/database/data"
os.makedirs(output_dir, exist_ok=True)
output_file = os.path.join(output_dir, "predefined_farms.json")

try:
    xl = pd.ExcelFile(file_path)
    df = xl.parse('туман Руйхат 11 (2)', header=None)
    
    farms = []
    
    for idx, row in df.iterrows():
        val0 = row[0]
        try:
            if pd.notnull(val0):
                f = float(val0)
                if f.is_integer() and f > 0:
                    name = str(row[1]).strip() if pd.notnull(row[1]) else None
                    
                    stir = None
                    if pd.notnull(row[3]):
                        stir_val = row[3]
                        if isinstance(stir_val, float):
                            stir = str(int(stir_val)).strip()
                        else:
                            stir = str(stir_val).strip()
                    
                    # Only keep genuine farms (which have a name and a STIR, and are not totals)
                    if name and stir and "Жами массив" not in name:
                        crop_type = str(row[2]).strip() if pd.notnull(row[2]) else None
                        
                        size = None
                        if pd.notnull(row[5]):
                            try:
                                size = float(row[5])
                            except:
                                pass
                                
                        farms.append({
                            "name": name,
                            "crop_type": crop_type,
                            "stir": stir,
                            "size": size
                        })
        except:
            pass
            
    print(f"Total genuine farms parsed: {len(farms)}")
    
    # Save list to JSON
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(farms, f, ensure_ascii=False, indent=2)
        
    print(f"Successfully saved to {output_file}")
    
except Exception as e:
    print("Error:", e)
