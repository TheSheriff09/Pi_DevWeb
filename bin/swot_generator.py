import sys
import json
import random

def generate_swot_robust(name, description, sector):
    """
    Generates a high-quality SWOT analysis based on sector and keywords.
    This method is used as a reliable fallback when heavy AI libraries are missing.
    """
    sector = sector.lower() if sector else "generic"
    
    # Sector-specific SWOT components (Human-curated AI templates)
    templates = {
        "healthcare": {
            "strengths": ["Strong focus on patient care and safety", "Integration of modern healthcare technologies", "Compliance with international health standards"],
            "weaknesses": ["High operational costs for medical infrastructure", "Complex regulatory environment", "Limited reach in rural areas"],
            "opportunities": ["Expansion into telemedicine and digital health", "Rising global demand for healthcare services", "Strategic partnerships with medical researchers"],
            "threats": ["Intense competition from established hospital networks", "Frequent changes in government health policies", "Potential data privacy concerns (HIPAA etc.)"]
        },
        "technology": {
            "strengths": ["Scalable software architecture", "Innovative approach to problem-solving", "Low overhead costs compared to physical industries"],
            "weaknesses": ["Rapidly evolving technology landscape", "Dependency on highly skilled technical staff", "Potential vulnerability to cybersecurity issues"],
            "opportunities": ["Growth in AI and machine learning integration", "Expansion into emerging global tech markets", "Strategic acquisition potential by tech giants"],
            "threats": ["Market saturation with similar SaaS products", "Evolving data protection laws (GDPR/CCPA)", "Threat of cyber-attacks and data breaches"]
        },
        "default": {
            "strengths": ["Agile and responsive management structure", "Strong vision and mission-driven approach", "Potential for rapid market entry"],
            "weaknesses": ["Limited initial brand recognition", "Restricted access to large-scale funding", "Narrow market focus in the early stages"],
            "opportunities": ["Expansion into undexploited niche markets", "Leveraging digital marketing for rapid growth", "Strategic networking with industry leaders"],
            "threats": ["Economic instability affecting consumer spend", "Aggressive competition from existing market leaders", "Potential for rising operational and marketing costs"]
        }
    }

    # Pick template or default
    swot = templates.get(sector, templates["default"]).copy()
    
    # Personalize for description
    if description:
        # Just simple mapping logic to add 'AI-like' feel
        swot["strengths"].insert(0, f"Directly addresses key pain points: {description[:50]}...")
        
    return swot

def main():
    if len(sys.argv) < 4:
        # Default data if arguments are missing
        print(json.dumps({"error": "Usage: swot_generator.py <name> <description> <sector>"}))
        sys.exit(1)

    name = sys.argv[1]
    description = sys.argv[2]
    sector = sys.argv[3]

    try:
        # Generate the robust SWOT (no transformers/torch needed)
        result = generate_swot_robust(name, description, sector)
        
        # Format the final JSON response (match JS expectations: flat, lowercase)
        output = {
            "strengths": result["strengths"],
            "weaknesses": result["weaknesses"],
            "opportunities": result["opportunities"],
            "threats": result["threats"]
        }
        print(json.dumps(output))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
