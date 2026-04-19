import sys
import json

def generate_business_plan(name, sector, description, funding, target_market):
    """
    Generates a structured business plan based on inputs.
    """
    
    # Section generation logic (simulated AI)
    plan = {
        "title": f"Business Plan for {name}",
        "description": f"{name} is a high-growth startup operating within the {sector} sector. Our primary mission is to {description.lower()}. We are currently addressing the significant needs of {target_market}.",
        
        "executiveSummary": f"Executive Summary: {name} aims to disrupt the {sector} market by providing innovative solutions tailored for {target_market}. With an initial funding requirement of ${funding}, we plan to scale our operations and achieve market leadership within the next 24 months.",
        
        "marketAnalysis": f"The {sector} market is currently undergoing a digital transformation. Our research shows that {target_market} is an underserved segment with high growth potential. Competitors currently lack the specific focus on {description[:50]}... that {name} provides.",
        
        "valueProposition": f"Our value proposition centers on efficiency, transparency, and innovation. We bridge the gap between traditional {sector} services and the modern needs of {target_market}.",
        
        "businessModel": f"{name} utilizes a scalable business model focused on customer acquisition and long-term retention. Our primary revenue streams involve subscription tiers and transactional fees optimized for the {sector} industry.",
        
        "marketingStrategy": f"Our go-to-market strategy involves a multi-channel approach: \n1. Strategic partnerships in the {sector} space.\n2. Digital content marketing targeting {target_market}.\n3. Direct sales outreach to high-value early adopters.",
        
        "financialForecast": f"We are seeking ${funding} to cover initial R&D and market entry costs. We project to reach break-even point in Q3 of year 2, with a steady 15% month-on-month growth in revenue thereafter.",
        
        "timeline": "Phase 1 (M1-M6): Platform development & Beta launch\nPhase 2 (M7-M12): Market entry & First 1000 customers\nPhase 3 (M13-M24): Scale-up & Series A funding",
        "fundingRequired": funding
    }

    return plan

def main():
    if len(sys.argv) < 6:
        print(json.dumps({"error": "Usage: business_plan_generator.py <name> <sector> <description> <funding> <target_market>"}))
        sys.exit(1)

    try:
        name = sys.argv[1]
        sector = sys.argv[2]
        description = sys.argv[3]
        funding = sys.argv[4]
        target_market = sys.argv[5]
        
        result = generate_business_plan(name, sector, description, funding, target_market)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
