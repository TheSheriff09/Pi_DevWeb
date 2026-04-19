import pymysql
import sys
import json
import math
import pickle
import os

MODEL_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'funding_model.pkl')

DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'startupflow',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

def load_data_from_db():
    connection = pymysql.connect(**DB_CONFIG)
    with connection.cursor() as cursor:
        query = "SELECT paymentSchedule, amount, status FROM fundingapplication WHERE status IS NOT NULL"
        cursor.execute(query)
        rows = cursor.fetchall()
    connection.close()
    
    data = []
    for r in rows:
        status_lower = str(r['status']).lower()
        if 'pending' in status_lower or 'attente' in status_lower:
            continue
            
        target = 1 if status_lower in ['approuvé', 'approved', 'accepted'] else 0
        data.append({
            'schedule': str(r['paymentSchedule']).strip() if r['paymentSchedule'] else '',
            'amount': float(r['amount']) if r['amount'] is not None else 0.0,
            'target': target
        })
        
    has_approved = any(d['target'] == 1 for d in data)
    has_rejected = any(d['target'] == 0 for d in data)
    
    if not has_approved or len(data) == 0:
        data.extend([
            {'schedule': 'Milestone-based Tranches', 'amount': 25000.0, 'target': 1},
            {'schedule': 'Quarterly Installments', 'amount': 75000.0, 'target': 1}
        ])
    if not has_rejected or len(data) == 0:
        data.extend([
            {'schedule': 'Monthly Operations', 'amount': 1000.0, 'target': 0},
            {'schedule': 'Upfront (Lump Sum)', 'amount': 250000.0, 'target': 0}
        ])
        
    return data

def train_and_evaluate_model():
    data = load_data_from_db()
    if len(data) < 1:
        print("Not enough data to train.")
        return
        
    class_counts = {0: 0, 1: 0}
    schedule_counts = {0: {}, 1: {}}
    amount_sum = {0: 0.0, 1: 0.0}
    amount_sq_sum = {0: 0.0, 1: 0.0}
    
    for d in data:
        c = d['target']
        class_counts[c] += 1
        amount_sum[c] += d['amount']
        amount_sq_sum[c] += d['amount'] ** 2
        
        s = d['schedule']
        schedule_counts[c][s] = schedule_counts[c].get(s, 0) + 1
            
    model = {
        'class_counts': class_counts,
        'schedule_counts': schedule_counts,
        'total': len(data),
        'amount_stats': {}
    }
    
    for c in [0, 1]:
        n = max(class_counts[c], 1)
        mean = amount_sum[c] / n
        var = (amount_sq_sum[c] / n) - (mean ** 2)
        model['amount_stats'][c] = {'mean': mean, 'var': max(var, 1e-4)}
        
    with open(MODEL_PATH, 'wb') as f:
        pickle.dump(model, f)
    print("Model trained and saved to", MODEL_PATH)

def predict_approval(schedule, amount):
    try:
        with open(MODEL_PATH, 'rb') as f:
            model = pickle.load(f)
    except FileNotFoundError:
        return 0.0
        
    class_counts = model['class_counts']
    schedule_counts = model['schedule_counts']
    amount_stats = model['amount_stats']
    total = sum(class_counts.values()) or 1
    
    def calculate_prob(c):
        if class_counts[c] == 0:
            return -float('inf') 
        
        prob = math.log((class_counts[c] + 0.1) / (total + 0.2))
        
        vocab_size = len(set(list(schedule_counts[0].keys()) + list(schedule_counts[1].keys())))
        total_schedules = sum(schedule_counts[c].values())
        
        count = schedule_counts[c].get(schedule.strip(), 0)
        prob += math.log((count + 1) / (total_schedules + vocab_size + 1))
            
        mean = amount_stats[c]['mean']
        var = amount_stats[c]['var']
        var = max(var, 1e-8)  
        prob += -0.5 * math.log(2 * math.pi * var) - ((amount - mean)**2) / (2 * var)
        
        return prob

    p0 = calculate_prob(0)
    p1 = calculate_prob(1)
    
    if p0 == -float('inf') and p1 == -float('inf'):
        return 0.5
    if p0 == -float('inf'):
        return 1.0
    if p1 == -float('inf'):
        return 0.0
        
    max_p = max(p0, p1)
    val0 = math.exp(p0 - max_p)
    val1 = math.exp(p1 - max_p)
    return val1 / (val0 + val1)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python train_funding_model.py [train|predict] ...")
        sys.exit(1)
        
    action = sys.argv[1]
    
    if action == "train":
        train_and_evaluate_model()
    elif action == "predict":
        if len(sys.argv) < 4:
            print(json.dumps({"error": "Missing arguments"}))
            sys.exit(1)
        schedule = sys.argv[2]
        try:
            amount = float(sys.argv[3])
        except ValueError:
            print(json.dumps({"error": "Amount must be a number."}))
            sys.exit(1)
            
        prob = predict_approval(schedule, amount)
        print(json.dumps({"probability": float(prob)}))
    else:
        sys.exit(1)
