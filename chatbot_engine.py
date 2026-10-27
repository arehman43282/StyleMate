import sys
import json
import mysql.connector
import re
from datetime import datetime

# Database Configuration
DB_CONFIG = {
    'user': 'root',
    'password': 'root',
    'host': '127.0.0.1',
    'port': 8889,
    'database': 'dashboard'
}

def get_db_connection():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except mysql.connector.Error as err:
        return None

def match_intent(message):
    message = message.lower()
    
    # Intent Patterns
    patterns = {
        'greeting': [r'\b(hi|hello|hey|greetings|morning|evening)\b'],
        'track_order': [r'\b(track|order|status|where is my)\b'],
        'return_policy': [r'\b(return|refund|exchange|back)\b'],
        'shipping_policy': [r'\b(shipping|delivery|ship|cost|charge)\b'],
        'contact': [r'\b(contact|support|help|call|email)\b'],
        'about': [r'\b(who are you|what is this)\b']
    }
    
    for intent, regex_list in patterns.items():
        for pattern in regex_list:
            if re.search(pattern, message):
                return intent
    
    return 'search' # Default to product search

def handle_greeting():
    return {
        "response": "Hello! I'm your Python-powered SmartFit Assistant. I can help you find products, track orders, or answer questions about our policies.",
        "actions": []
    }

def handle_tracking(user_id):
    if not user_id or user_id == '0':
        return {
            "response": "Please log in to track your orders.",
            "actions": [{"text": "Login", "link": "login.php"}]
        }
    
    conn = get_db_connection()
    if not conn:
        return {"response": "System error: Unable to connect to database.", "actions": []}
    
    try:
        cursor = conn.cursor(dictionary=True)
        query = """
            SELECT t.Order_ID, t.Status, t.Delivery_Date, t.Order_Date 
            FROM Track_Orders t 
            WHERE t.User_ID = %s 
            ORDER BY t.Order_ID DESC LIMIT 1
        """
        cursor.execute(query, (user_id,))
        order = cursor.fetchone()
        
        if order:
            status_map = {
                'P': 'Pending', 'S': 'Shipped', 'D': 'Delivered', 
                'C': 'Cancelled', 'R': 'Rejected', 'Assigned': 'Processing',
                'Pending': 'Pending', 'Confirmed': 'Confirmed'
            }
            raw_status = order['Status']
            status = status_map.get(raw_status, raw_status)
            
            date_label = "Delivered on" if status == 'Delivered' else "Expected Delivery"
            date_str = "soon"
            if order['Delivery_Date']:
                # Format date assuming it might be datetime or string
                d = order['Delivery_Date']
                if isinstance(d, str):
                    # Try parsing if string
                    try:
                        d_obj = datetime.strptime(d, '%Y-%m-%d %H:%M:%S')
                        date_str = d_obj.strftime('%d %b %Y')
                    except:
                        date_str = d
                else:
                    date_str = d.strftime('%d %b %Y')
            
            return {
                "response": f"Your latest order (#{order['Order_ID']}) is currently **{status}**. {date_label}: {date_str}. Want to check another?",
                "actions": [{"text": "View All Orders", "link": "my_orders.php"}]
            }
        else:
            return {
                "response": "I couldn't find any recent orders. Time to start shopping!",
                "actions": [{"text": "Start Shopping", "link": "women_fashion.php"}]
            }
    except Exception as e:
        return {"response": f"Error retrieving order: {str(e)}", "actions": []}
    finally:
        conn.close()

def handle_search(message):
    # Extract keywords (remove stop words)
    stop_words = ['i', 'want', 'to', 'buy', 'looking', 'for', 'show', 'me', 'do', 'you', 'have', 'a', 'the', 'some', 'can', 'find', 'get']
    words = message.lower().split()
    keywords = [w for w in words if w not in stop_words]
    search_term = ' '.join(keywords)
    
    if len(search_term) < 2:
        return {
            "response": "I'm not sure what you're looking for. Try searching for 'sarees', 'jeans', or 'shoes'.",
            "actions": []
        }
        
    conn = get_db_connection()
    if not conn:
        return {"response": "System error: Database connection failed.", "actions": []}
        
    try:
        cursor = conn.cursor(dictionary=True)
        # Using LIKE for simple search
        query = "SELECT Products_ID, Products_Name, price, image FROM products WHERE Products_Name LIKE %s OR Description LIKE %s LIMIT 3"
        like_term = f"%{search_term}%"
        cursor.execute(query, (like_term, like_term))
        results = cursor.fetchall()
        
        if results:
            actions = []
            for row in results:
                img = row['image']
                if not img.startswith('http'):
                    img = 'images/products/' + img
                
                actions.append({
                    "text": f"{row['Products_Name']} - ₹{row['price']}",
                    "link": f"product_detail.php?id={row['Products_ID']}",
                    "image": img
                })
            
            return {
                "response": f"I found these items matching '{search_term}':",
                "actions": actions
            }
        else:
            return {
                "response": f"Sorry, I couldn't find anything matching '{search_term}'.",
                "actions": [{"text": "Browse Categories", "link": "index.php"}]
            }
            
    except Exception as e:
        return {"response": f"Search error: {str(e)}", "actions": []}
    finally:
        conn.close()

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"response": "Error: No input provided.", "actions": []}))
        sys.exit(1)
        
    message = sys.argv[1]
    user_id = sys.argv[2] if len(sys.argv) > 2 else '0'
    
    intent = match_intent(message)
    
    if intent == 'greeting':
        result = handle_greeting()
    elif intent == 'track_order':
        result = handle_tracking(user_id)
    elif intent == 'return_policy':
        result = {"response": "You can return items within 7 days of delivery via your dashboard.", "actions": []}
    elif intent == 'shipping_policy':
        result = {"response": "We ship worldwide! Standard delivery is 3-5 days.", "actions": []}
    elif intent == 'contact':
        result = {"response": "Contact us at support@smartfit.com or +1-800-SMARTFIT.", "actions": []}
    elif intent == 'about':
        result = {"response": "I am a Python-based chatbot designed to assist you with your shopping experience.", "actions": []}
    else:
        result = handle_search(message)
        
    print(json.dumps(result))

if __name__ == "__main__":
    main()
