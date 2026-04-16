import sys
import json
import random
import math

def generate_forecast(revenue, growth_rate, expenses):
    """
    Generates a 12-month financial forecast based on inputs.
    revenue: Initial monthly revenue
    growth_rate: Monthly growth rate (e.g., 0.1 for 10%)
    expenses: Initial monthly expenses
    """
    months = []
    current_rev = revenue
    current_exp = expenses
    
    # Simple simulation logic with some "AI" variability
    for i in range(1, 13):
        # Add slight random fluctuation to growth (-1% to +2% of original growth)
        actual_growth = growth_rate + (random.uniform(-0.01, 0.02))
        current_rev = current_rev * (1 + actual_growth)
        
        # Expenses usually grow slower but have some fixed and variable parts
        # Let's say 20% of expenses are variable with revenue growth
        current_exp = current_exp * (1 + (actual_growth * 0.2))
        
        profit = current_rev - current_exp
        
        months.append({
            "month": i,
            "revenue": round(current_rev, 2),
            "expenses": round(current_exp, 2),
            "profit": round(profit, 2)
        })

    # Generate recommendation based on trends
    total_profit = sum(m['profit'] for m in months)
    avg_growth = (months[-1]['revenue'] / months[0]['revenue']) ** (1/11) - 1
    
    recommendation = ""
    if total_profit > 0 and avg_growth > 0.05:
        recommendation = "STRONGLY RECOMMEND INVESTMENT. The startup shows healthy exponential growth and sustainable unit economics. Projected annual profitability is strong."
    elif total_profit > 0:
        recommendation = "STABLE INVESTMENT. Consistent performance with positive cash flow. Good for risk-averse portfolios."
    elif avg_growth > 0.15:
        recommendation = "HIGH-RISK, HIGH-REWARD. Currently burning cash but showing hyper-growth. Recommended for venture capital seeking market share over immediate profit."
    else:
        recommendation = "CAUTION ADVISED. Forecast shows limited growth or significant burn rate. Suggest pivoting or optimizing burn before seeking external funding."

    return {
        "forecast": months,
        "summary": {
            "total_revenue": round(sum(m['revenue'] for m in months), 2),
            "total_expenses": round(sum(m['expenses'] for m in months), 2),
            "total_profit": round(total_profit, 2),
            "avg_monthly_growth": round(avg_growth * 100, 2)
        },
        "recommendation": recommendation
    }

def main():
    if len(sys.argv) < 4:
        print(json.dumps({"error": "Usage: forecast_generator.py <revenue> <growth_rate> <expenses>"}))
        sys.exit(1)

    try:
        revenue = float(sys.argv[1])
        growth_rate = float(sys.argv[2]) / 100 # convert percentage to decimal
        expenses = float(sys.argv[3])
        
        result = generate_forecast(revenue, growth_rate, expenses)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
